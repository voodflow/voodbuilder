<?php

namespace Voodflow\Voodbuilder\Filament\Resources;

use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use Voodflow\Voodbuilder\Filament\Resources\ModelIntegrationResource\Pages;
use Voodflow\Voodbuilder\Models\ModelIntegration;
use Voodflow\Voodbuilder\Support\ModelRegistry;
use Voodflow\Voodbuilder\Support\ReverseRelationRegistry;

class ModelIntegrationResource extends Resource
{
    protected static ?string $model = ModelIntegration::class;

    /**
     * Avoid URL collisions with host app resources.
     */
    protected static ?string $slug = 'voodbuilder/model-integrations';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-link';

    protected static array $modelMetadataCache = [];

    public static function canAccess(): bool
    {
        /** @var Authorizable|null $user */
        $user = Auth::user();

        if (! is_object($user)) {
            return false;
        }

        return $user->can('viewAny', ModelIntegration::class);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('voodbuilder::admin.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('voodbuilder::model_integrations.navigation.label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Group::make([
                    Section::make(__('voodbuilder::model_integrations.sections.details'))
                        ->icon('heroicon-o-cube')
                        ->compact()
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label(__('voodbuilder::model_integrations.fields.name'))
                                ->required(),
                            Forms\Components\Select::make('model_class')
                                ->label(__('voodbuilder::model_integrations.fields.model_class'))
                                ->required()
                                ->unique(ModelIntegration::class, 'model_class', ignoreRecord: true)
                                ->searchable()
                                ->preload()
                                ->options(fn () => static::getAvailableModelOptions())
                                ->getSearchResultsUsing(fn (string $search): array => static::getAvailableModelOptions($search))
                                ->getOptionLabelUsing(fn (?string $value): ?string => $value ? class_basename($value) : null)
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set, Get $get): void {
                                    $set('fields', [
                                        'essential' => [],
                                        'relations' => [],
                                    ]);

                                    if (! $get('model_alias') && is_string($state)) {
                                        $set('model_alias', Str::camel(class_basename($state)));
                                    }
                                }),
                            Forms\Components\TextInput::make('model_alias')
                                ->label(__('voodbuilder::model_integrations.fields.model_alias'))
                                ->helperText(__('voodbuilder::model_integrations.helpers.model_alias')),
                        ])->columns(1),
                ])->columnSpan(4),
                Group::make([
                    Tabs::make('fields_tabs')
                        ->id('model-integration-tabs')
                        ->persistTabInQueryString()
                        ->tabs([
                            Tab::make('essential_fields')
                                ->label(__('voodbuilder::model_integrations.fields.essential_fields'))
                                ->icon('heroicon-o-list-bullet')
                                ->schema([
                                    Forms\Components\CheckboxList::make('fields.essential')
                                        ->label(__('voodbuilder::model_integrations.fields.essential_fields'))
                                        ->options(fn (Get $get): array => static::getModelFieldOptions(static::resolveModelClass($get)))
                                        ->columns(3)
                                        ->searchable()
                                        ->bulkToggleable(),
                                ]),
                            Tab::make('relations')
                                ->label(__('voodbuilder::model_integrations.fields.relations'))
                                ->icon('heroicon-o-circle-stack')
                                ->schema([
                                    Forms\Components\Repeater::make('fields.relations')
                                        ->label(__('voodbuilder::model_integrations.fields.relations'))
                                        ->itemLabel(fn (array $state): ?string => ($state['name'] ?? ''))
                                        ->schema([
                                            Forms\Components\Select::make('name')
                                                ->label(__('voodbuilder::model_integrations.fields.relation_name'))
                                                ->options(fn (Get $get): array => static::getRelationOptions(static::resolveModelClass($get)))
                                                ->searchable()
                                                ->preload()
                                                ->reactive()
                                                ->live()
                                                ->afterStateHydrated(function ($state, callable $set, Get $get): void {
                                                    if (! $state) {
                                                        return;
                                                    }

                                                    static::syncRelationMetadata($state, $set, $get);
                                                })
                                                ->afterStateUpdated(function ($state, callable $set, Get $get): void {
                                                    static::syncRelationMetadata($state, $set, $get);
                                                    $set('fields', []);
                                                    $set('expand', []);
                                                })
                                                ->required(),
                                            Forms\Components\Hidden::make('related_class'),
                                            Forms\Components\Hidden::make('relation_mode')->default('direct'),
                                            Forms\Components\Hidden::make('relation_descriptor'),
                                            Forms\Components\TextInput::make('alias')
                                                ->label(__('voodbuilder::model_integrations.fields.relation_alias'))
                                                ->placeholder('loans_sent')
                                                ->helperText(__('voodbuilder::model_integrations.helpers.relation_alias')),
                                            Forms\Components\CheckboxList::make('fields')
                                                ->label(__('voodbuilder::model_integrations.fields.relation_fields'))
                                                ->options(fn (Get $get): array => static::getRelationFieldOptions($get))
                                                ->columns(3)
                                                ->searchable()
                                                ->bulkToggleable()
                                                ->columnSpan(2),
                                            Forms\Components\Repeater::make('nested_relations')
                                                ->label(__('voodbuilder::model_integrations.fields.nested_relations'))
                                                ->helperText(__('voodbuilder::model_integrations.helpers.nested_relations'))
                                                ->itemLabel(fn (array $state): ?string => ($state['relation'] ?? '').' → '.implode(', ', $state['fields'] ?? []))
                                                ->schema([
                                                    Forms\Components\Select::make('relation')
                                                        ->label(__('voodbuilder::model_integrations.fields.expand_relation'))
                                                        ->options(fn (Get $get): array => static::getRelationExpandOptions($get))
                                                        ->required()
                                                        ->searchable()
                                                        ->live()
                                                        ->reactive()
                                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                                    Forms\Components\CheckboxList::make('fields')
                                                        ->label(__('voodbuilder::model_integrations.fields.fields_to_load'))
                                                        ->helperText(__('voodbuilder::model_integrations.helpers.fields_optional'))
                                                        ->options(fn (Get $get): array => static::getExpandRelationFieldOptions($get))
                                                        ->columns(3),
                                                ])
                                                ->default([])
                                                ->addActionLabel(__('voodbuilder::model_integrations.actions.add_nested_relation'))
                                                ->reorderable()
                                                ->collapsed()
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(2)
                                        ->default([])
                                        ->addActionLabel(__('voodbuilder::model_integrations.actions.add_relation'))
                                        ->reorderable()
                                        ->collapsed(),
                                ]),

                        ]),
                ])
                    ->columnSpan(8),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('voodbuilder::model_integrations.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('model_class')
                    ->label(__('voodbuilder::model_integrations.fields.model_class'))
                    ->copyable()
                    ->copyMessage(__('filament::components/copyable.messages.copied'))
                    ->copyMessageDuration(1500)
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('model_alias')
                    ->label(__('voodbuilder::model_integrations.fields.model_alias'))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('voodbuilder::model_integrations.fields.updated_at'))
                    ->dateTime(),
            ])
            ->recordActions([
                EditAction::make()->slideOver(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModelIntegrations::route('/'),
            'create' => Pages\CreateModelIntegration::route('/create'),
            'edit' => Pages\EditModelIntegration::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function getModelFieldOptions(?string $modelClass): array
    {
        return self::analyzeModel($modelClass)['fields'];
    }

    protected static function getRelationOptions(?string $modelClass): array
    {
        $options = [];

        foreach (self::analyzeModel($modelClass)['relations'] as $name => $relatedClass) {
            $label = Str::headline($name);
            if ($relatedClass) {
                $label .= ' ('.class_basename($relatedClass).')';
            }

            $options[$name] = $label;
        }

        foreach (app(ReverseRelationRegistry::class)->for($modelClass) as $descriptor) {
            $key = 'reverse::'.$descriptor['key'];
            $options[$key] = $descriptor['label'].' · '.__('voodbuilder::model_integrations.labels.reverse');
        }

        return $options;
    }

    protected static function getRelatedModelClass(?string $modelClass, ?string $relationName): ?string
    {
        if (! $modelClass || ! $relationName) {
            return null;
        }

        return self::analyzeModel($modelClass)['relations'][$relationName] ?? null;
    }

    /**
     * @return array{fields: array<string, string>, relations: array<string, string>}
     */
    protected static function analyzeModel(?string $modelClass): array
    {
        if (! $modelClass || ! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return [
                'fields' => [],
                'relations' => [],
            ];
        }

        if (isset(self::$modelMetadataCache[$modelClass])) {
            return self::$modelMetadataCache[$modelClass];
        }

        try {
            $model = app($modelClass);
        } catch (\Throwable $exception) {
            return [
                'fields' => [],
                'relations' => [],
            ];
        }

        $fields = $model->getFillable();

        if (empty($fields)) {
            try {
                $table = $model->getTable();
                if ($table && DatabaseSchema::hasTable($table)) {
                    $fields = DatabaseSchema::getColumnListing($table);
                }
            } catch (\Throwable $exception) {
                // ignore
            }
        }

        $fieldOptions = collect($fields ?? [])
            ->merge(['id', 'created_at', 'updated_at'])
            ->unique()
            ->mapWithKeys(fn ($field) => [$field => Str::headline(str_replace('_', ' ', $field))])
            ->toArray();

        $relations = [];

        try {
            $reflection = new ReflectionClass($modelClass);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (
                    $method->isStatic()
                    || $method->isAbstract()
                    || $method->getName() === '__construct'
                    || $method->getNumberOfParameters() > 0
                ) {
                    continue;
                }

                try {
                    $relation = $model->{$method->getName()}();

                    if ($relation instanceof Relation) {
                        $relations[$method->getName()] = get_class($relation->getRelated());
                    }
                } catch (\Throwable $exception) {
                    continue;
                }
            }
        } catch (\Throwable $exception) {
            // ignore
        }

        return self::$modelMetadataCache[$modelClass] = [
            'fields' => $fieldOptions,
            'relations' => $relations,
        ];
    }

    protected static function syncRelationMetadata(string $state, callable $set, Get $get): void
    {
        if (str_starts_with($state, 'reverse::')) {
            $descriptorKey = substr($state, 9);
            $descriptor = app(ReverseRelationRegistry::class)->find($descriptorKey);

            $set('relation_mode', 'reverse');
            $set('relation_descriptor', $descriptorKey);
            $set('related_class', $descriptor['source_model'] ?? null);

            if (blank($get('alias'))) {
                $set('alias', static::defaultReverseAlias($descriptor));
            }

            return;
        }

        $relatedClass = static::getRelatedModelClass(static::resolveModelClass($get), $state);
        $set('relation_mode', 'direct');
        $set('relation_descriptor', null);
        $set('related_class', $relatedClass);

        if (blank($get('alias'))) {
            $set('alias', static::defaultDirectAlias($state));
        }
    }

    protected static function getRelationFieldOptions(Get $get): array
    {
        $mode = static::resolveRelationMode($get);

        if ($mode === 'reverse') {
            $descriptorKey = static::resolveRelationDescriptorKey($get);
            if (! $descriptorKey) {
                return [];
            }

            $descriptor = app(ReverseRelationRegistry::class)->find($descriptorKey);
            if (! $descriptor) {
                return [];
            }

            // Reverse relations: use essential fields from the source model (e.g. EquipmentLoan)
            // and nested relation paths (e.g. unit.inventory_code, unit.model.name)
            $sourceModel = $descriptor['source_model'] ?? null;
            if ($sourceModel && class_exists($sourceModel)) {
                $registry = app(ModelRegistry::class);
                $modelFields = $registry->getFields($sourceModel);

                $allFieldOptions = [];

                // Essential fields
                if ($modelFields && isset($modelFields['essential'])) {
                    $essentialFields = $modelFields['essential'];
                    $fieldNames = [];
                    foreach ($essentialFields as $key => $value) {
                        if (is_int($key)) {
                            $fieldNames[] = $value;
                        } else {
                            $fieldNames[] = $key;
                        }
                    }
                    $allFieldOptions = array_merge($allFieldOptions, static::formatFieldOptions($fieldNames, $sourceModel));
                }

                // Nested relation fields (recursive)
                if ($modelFields && isset($modelFields['relations'])) {
                    static::collectNestedRelationFields(
                        $modelFields['relations'],
                        $sourceModel,
                        $registry,
                        $allFieldOptions,
                        ''
                    );
                }

                if (! empty($allFieldOptions)) {
                    return $allFieldOptions;
                }
            }

            // Fallback: fields declared on the relation descriptor
            $fields = $descriptor['model_fields']['fields'] ?? [];

            return static::formatFieldOptions($fields, $sourceModel);
        }

        $relatedClass = static::resolveRelatedClass($get);
        if (! $relatedClass) {
            return [];
        }

        return static::getModelFieldOptions($relatedClass);
    }

    /**
     * Get field options for an expanded (nested) relation.
     * Used when configuring which fields to load for each expanded relation.
     */
    protected static function getExpandRelationFieldOptions(Get $get): array
    {
        $nestedRel = $get('relation');
        $relatedClass = $get('../../related_class') ?? $get('../related_class');

        if (! $nestedRel || ! $relatedClass) {
            return [];
        }

        $nestedModelClass = static::getRelatedModelClassFromRelation($relatedClass, $nestedRel);

        return $nestedModelClass ? static::getModelFieldOptions($nestedModelClass) : [];
    }

    protected static function getRelationExpandOptions(Get $get): array
    {
        $mode = static::resolveRelationMode($get);

        if ($mode === 'reverse') {
            $descriptorKey = static::resolveRelationDescriptorKey($get);
            if (! $descriptorKey) {
                return [];
            }

            $descriptor = app(ReverseRelationRegistry::class)->find($descriptorKey);
            if (! $descriptor) {
                return [];
            }

            $expand = $descriptor['model_fields']['expand'] ?? [];

            return array_combine($expand, $expand);
        }

        $relatedClass = static::resolveRelatedClass($get);
        if (! $relatedClass) {
            return [];
        }

        $relations = self::analyzeModel($relatedClass)['relations'];
        $mainClass = static::resolveModelClass($get);

        $options = [];
        foreach ($relations as $name => $class) {
            if ($class === $mainClass || $class === $relatedClass) {
                continue;
            }

            $options[$name] = Str::headline($name);
        }

        return $options;
    }

    protected static function defaultDirectAlias(string $relationName): string
    {
        return Str::camel($relationName);
    }

    protected static function defaultReverseAlias(?array $descriptor): string
    {
        if (! $descriptor) {
            return Str::camel('reverse_relation');
        }

        return Str::camel(class_basename($descriptor['source_model'] ?? 'relation').'_'.($descriptor['relation_name'] ?? 'related'));
    }

    protected static function resolveRelationMode(Get $get): string
    {
        $paths = [
            'relation_mode',
            '../relation_mode',
            '../../relation_mode',
            '../../../relation_mode',
        ];

        foreach ($paths as $path) {
            $value = $get($path);
            if ($value) {
                return $value;
            }
        }

        return 'direct';
    }

    protected static function resolveRelatedClass(Get $get): ?string
    {
        $paths = [
            'related_class',
            '../related_class',
            '../../related_class',
            '../../../related_class',
        ];

        foreach ($paths as $path) {
            $value = $get($path);
            if ($value) {
                return $value;
            }
        }

        return null;
    }

    protected static function resolveRelationDescriptorKey(Get $get): ?string
    {
        $paths = [
            'relation_descriptor',
            '../relation_descriptor',
            '../../relation_descriptor',
            '../../../relation_descriptor',
        ];

        foreach ($paths as $path) {
            $value = $get($path);
            if ($value) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Resolve the related model class for an Eloquent relation name via reflection.
     */
    protected static function getRelatedModelClassFromRelation(string $modelClass, string $relationName): ?string
    {
        if (! class_exists($modelClass)) {
            return null;
        }

        try {
            $model = new $modelClass;

            // Relation method must exist
            if (! method_exists($model, $relationName)) {
                return null;
            }

            // Invoke relation method
            $relation = $model->{$relationName}();

            // Eloquent relation: getRelated()
            if (method_exists($relation, 'getRelated')) {
                $relatedModel = $relation->getRelated();

                return get_class($relatedModel);
            }

            // Fallback: return type on the relation method
            $reflection = new ReflectionClass($modelClass);
            if ($reflection->hasMethod($relationName)) {
                $method = $reflection->getMethod($relationName);
                $returnType = $method->getReturnType();

                if ($returnType instanceof \ReflectionNamedType) {
                    $returnTypeClass = $returnType->getName();
                    // If return type is a Model subclass, use it
                    if (class_exists($returnTypeClass)) {
                        if (is_subclass_of($returnTypeClass, Model::class)) {
                            return $returnTypeClass;
                        }
                    }
                }
            }

            // Last resort: guess from relation name
            $guessedClass = static::guessModelClassFromRelationName($relationName, $modelClass);
            if ($guessedClass && class_exists($guessedClass)) {
                return $guessedClass;
            }
        } catch (\Throwable $e) {
            // Swallow errors
        }

        return null;
    }

    /**
     * Heuristic: guess related model class from a conventional relation name.
     */
    protected static function guessModelClassFromRelationName(string $relationName, string $currentModelClass): ?string
    {
        // Common names that often resolve to User
        $userRelations = ['borrower', 'loaner', 'author', 'creator', 'owner', 'created_by', 'updated_by', 'user'];

        if (in_array($relationName, $userRelations)) {
            return User::class;
        }

        // Studly case from relation name (e.g. unit -> Unit)
        $className = Str::studly($relationName);
        $namespace = substr($currentModelClass, 0, strrpos($currentModelClass, '\\'));

        // Try common namespaces
        $possibleClasses = [
            $namespace.'\\'.$className,
            'App\\Models\\'.$className,
            // 'Detit\\FilamentLabOps\\Models\\' . $className,
        ];

        foreach ($possibleClasses as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * Recursively collect selectable field keys for nested relations.
     *
     * @param  array<string, mixed>  $relations  Relation config from getVoodflowFields()
     * @param  string  $modelClass  Current Eloquent model class
     * @param  array<string, string>  &$allFieldOptions  Built option labels keyed by dotted path
     * @param  string  $basePath  Prefix (e.g. unit or unit.model)
     */
    protected static function collectNestedRelationFields(
        array $relations,
        string $modelClass,
        ModelRegistry $registry,
        array &$allFieldOptions,
        string $basePath = ''
    ): void {
        foreach ($relations as $relationName => $relationConfig) {
            $relationExpand = $relationConfig['expand'] ?? [];
            $relationFields = $relationConfig['fields'] ?? [];
            $relatedModelClass = static::getRelatedModelClassFromRelation($modelClass, $relationName);

            if (! $relatedModelClass) {
                continue;
            }

            $currentPath = $basePath === '' ? $relationName : "{$basePath}.{$relationName}";

            // Fields declared on this relation (e.g. unit.inventory_code)
            if (! empty($relationFields)) {
                $nestedFieldOptions = static::formatFieldOptions($relationFields, $relatedModelClass);
                foreach ($nestedFieldOptions as $fieldKey => $fieldLabel) {
                    $fullKey = "{$currentPath}.{$fieldKey}";
                    $labelPath = str_replace('.', ' → ', $currentPath);
                    $allFieldOptions[$fullKey] = "{$labelPath} → {$fieldLabel}";
                }
            }

            // Recurse into configured expands
            if (! empty($relationExpand)) {
                $relatedModelFields = $registry->getFields($relatedModelClass);

                // Child model has relation config in registry
                if ($relatedModelFields && isset($relatedModelFields['relations'])) {
                    // Only relations listed in expand
                    $nestedRelations = [];
                    foreach ($relationExpand as $nestedRelationName) {
                        if (isset($relatedModelFields['relations'][$nestedRelationName])) {
                            $nestedRelations[$nestedRelationName] = $relatedModelFields['relations'][$nestedRelationName];
                        } else {
                            // Unconfigured relation: stub entry so the user can still pick fields
                            $nestedRelatedModelClass = static::getRelatedModelClassFromRelation($relatedModelClass, $nestedRelationName);
                            if ($nestedRelatedModelClass) {
                                $nestedRelations[$nestedRelationName] = [
                                    'fields' => ['id', 'name'], // sensible defaults
                                    'expand' => [],
                                ];
                            }
                        }
                    }

                    // Recurse
                    if (! empty($nestedRelations)) {
                        static::collectNestedRelationFields(
                            $nestedRelations,
                            $relatedModelClass,
                            $registry,
                            $allFieldOptions,
                            $currentPath
                        );
                    }
                } else {
                    // No getVoodflowFields on related model: still offer id/name for expanded paths
                    foreach ($relationExpand as $nestedRelationName) {
                        $nestedRelatedModelClass = static::getRelatedModelClassFromRelation($relatedModelClass, $nestedRelationName);
                        if ($nestedRelatedModelClass) {
                            // Default id/name options
                            $commonFields = ['id', 'name'];
                            $nestedFieldOptions = static::formatFieldOptions($commonFields, $nestedRelatedModelClass);
                            foreach ($nestedFieldOptions as $fieldKey => $fieldLabel) {
                                $fullKey = "{$currentPath}.{$nestedRelationName}.{$fieldKey}";
                                $labelPath = str_replace('.', ' → ', $currentPath);
                                $allFieldOptions[$fullKey] = "{$labelPath} → {$nestedRelationName} → {$fieldLabel}";
                            }
                        }
                    }
                }
            }
        }
    }

    protected static function formatFieldOptions(array $fields, ?string $modelClass = null): array
    {
        $options = [];

        foreach ($fields as $key => $value) {
            if (is_int($key)) {
                $fieldKey = $value;
                // Prefer translation when present
                $label = static::getTranslatedFieldLabel($fieldKey, $modelClass);
                if (! $label) {
                    $label = Str::headline(str_replace('_', ' ', $fieldKey));
                }
            } else {
                $fieldKey = $key;
                $label = $value;
            }

            $options[$fieldKey] = $label;
        }

        return $options;
    }

    /**
     * Resolve a human label for a DB column (translation or headline).
     */
    protected static function getTranslatedFieldLabel(string $fieldKey, ?string $modelClass = null): ?string
    {
        if (! $modelClass) {
            return null;
        }

        // voodflow.fields.* translations
        $translationKey = "voodflow.fields.{$fieldKey}";
        $translated = trans($translationKey);

        if ($translated !== $translationKey) {
            return $translated;
        }

        // voodflow.models.{Model}.fields.*
        $modelName = class_basename($modelClass);
        $translationKey = "voodflow.models.{$modelName}.fields.{$fieldKey}";
        $translated = trans($translationKey);

        if ($translated !== $translationKey) {
            return $translated;
        }

        return null;
    }

    protected static function resolveModelClass(Get $get): ?string
    {
        $paths = [
            'model_class',
            '../model_class',
            '../../model_class',
            '../../../model_class',
            '../../../../model_class',
            '../../../../../model_class',
        ];

        foreach ($paths as $path) {
            $value = $get($path);
            if ($value) {
                return $value;
            }
        }

        if ($record = $get('record')) {
            return $record->model_class ?? null;
        }

        $requestData = request()->input('data');

        if (is_array($requestData) && isset($requestData['model_class'])) {
            return $requestData['model_class'];
        }

        return null;
    }

    /**
     * Discover Eloquent models from the Filament panel, app/Models, vendor packages, and app/packages.
     *
     * @param  string|null  $search  Optional filter substring
     * @return array<string, string> FQCN => display label
     */
    protected static function getAvailableModelOptions(?string $search = null): array
    {
        $excludedModels = config('voodbuilder.model_integrations.excluded_models', []);
        $models = [];

        // 1. Filament resources on the current panel
        try {
            $panel = Filament::getCurrentPanel();
            if ($panel) {
                $resources = $panel->getResources();
                foreach ($resources as $resourceClass) {
                    if (! class_exists($resourceClass)) {
                        continue;
                    }

                    try {
                        $reflection = new ReflectionClass($resourceClass);
                        if (! $reflection->hasProperty('model')) {
                            continue;
                        }

                        $modelProperty = $reflection->getStaticPropertyValue('model');
                        if ($modelProperty && is_string($modelProperty) && class_exists($modelProperty)) {
                            if (is_subclass_of($modelProperty, Model::class)) {
                                if (! static::isExcludedModelClass($modelProperty, $excludedModels)) {
                                    $models[$modelProperty] = $modelProperty;
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        // Swallow errors di reflection
                        continue;
                    }
                }
            }
        } catch (\Throwable $e) {
            // No panel context: continue with filesystem scan
        }

        // 2. app/Models
        $modelPaths = [
            app_path('Models'),
        ];

        foreach ($modelPaths as $path) {
            if (! File::exists($path)) {
                continue;
            }

            $files = File::allFiles($path);
            foreach ($files as $file) {
                $className = 'App\\Models\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
                $className = str_replace('App\\Models\\'.basename($path).'\\', 'App\\Models\\', $className);

                if (class_exists($className) && is_subclass_of($className, Model::class)) {
                    if (! static::isExcludedModelClass($className, $excludedModels)) {
                        $models[$className] = $className;
                    }
                }
            }
        }

        // 3. vendor/*/package/src/Models
        $vendorPath = base_path('vendor');
        if (File::exists($vendorPath)) {
            $vendorDirs = File::directories($vendorPath);
            foreach ($vendorDirs as $vendorDir) {
                $packageDirs = File::directories($vendorDir);
                foreach ($packageDirs as $packageDir) {
                    $modelsPath = $packageDir.'/src/Models';
                    if (File::exists($modelsPath)) {
                        $files = File::allFiles($modelsPath);
                        foreach ($files as $file) {
                            $relativePath = str_replace([$packageDir.'/src/', '.php'], ['', ''], $file->getPathname());
                            $className = str_replace('/', '\\', $relativePath);

                            // Namespace from composer.json psr-4 when possible
                            $composerPath = $packageDir.'/composer.json';
                            if (File::exists($composerPath)) {
                                $composer = json_decode(File::get($composerPath), true);
                                if (isset($composer['autoload']['psr-4'])) {
                                    foreach ($composer['autoload']['psr-4'] as $namespace => $path) {
                                        if (strpos($relativePath, $path) === 0 || $path === 'src/') {
                                            $className = rtrim($namespace, '\\').'\\'.str_replace(['src/', '/'], ['', '\\'], $relativePath);

                                            break;
                                        }
                                    }
                                }
                            }

                            if (class_exists($className) && is_subclass_of($className, Model::class)) {
                                if (! static::isExcludedModelClass($className, $excludedModels)) {
                                    $models[$className] = $className;
                                }
                            }
                        }
                    }
                }
            }
        }

        // 4. Local path packages (app/packages)
        $packagesPath = app_path('packages');
        if (File::exists($packagesPath)) {
            $vendorDirs = File::directories($packagesPath);
            foreach ($vendorDirs as $vendorDir) {
                $packageDirs = File::directories($vendorDir);
                foreach ($packageDirs as $packageDir) {
                    $modelsPath = $packageDir.'/src/Models';
                    if (File::exists($modelsPath)) {
                        $files = File::allFiles($modelsPath);
                        foreach ($files as $file) {
                            $relativePath = str_replace([$packageDir.'/src/Models/', '.php'], ['', ''], $file->getRelativePathname());

                            // Build FQCN from vendor/package folder names
                            $vendorName = basename($vendorDir);
                            $packageName = basename($packageDir);

                            // Sub-namespace from relative path
                            $namespaceParts = [];
                            if ($relativePath) {
                                $pathParts = explode('/', $relativePath);
                                $namespaceParts = array_map(fn ($part) => str_replace(' ', '', ucwords(str_replace('_', ' ', $part))), $pathParts);
                            }

                            $modelName = ! empty($namespaceParts) ? end($namespaceParts) : '';
                            $subNamespace = ! empty($namespaceParts) ? '\\'.implode('\\', array_slice($namespaceParts, 0, -1)) : '';

                            $className = "{$vendorName}\\{$packageName}\\Models{$subNamespace}\\{$modelName}";

                            if (class_exists($className) && is_subclass_of($className, Model::class)) {
                                if (! static::isExcludedModelClass($className, $excludedModels)) {
                                    $models[$className] = $className;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Optional search filter
        if ($search) {
            $models = array_filter($models, function ($modelClass) use ($search) {
                return stripos($modelClass, $search) !== false ||
                    stripos(class_basename($modelClass), $search) !== false;
            });
        }

        // Sort and build readable labels
        ksort($models);
        $options = [];
        foreach ($models as $modelClass) {
            $basename = class_basename($modelClass);
            $namespace = str_replace('\\'.$basename, '', $modelClass);

            // Readable label with package hint
            $label = $basename;
            if ($namespace && $namespace !== 'App\\Models') {
                // Derive plugin name from namespace
                $namespaceParts = explode('\\', $namespace);

                // Drop "Models" segment
                $namespaceParts = array_filter($namespaceParts, fn ($part) => $part !== 'Models');

                // Prefer last meaningful namespace segment
                if (count($namespaceParts) >= 2) {
                    // e.g. Base33\FilamentVoodflow → Voodflow
                    $packageName = end($namespaceParts);

                    // Strip "Filament" prefix for shorter labels
                    $packageName = str_replace('Filament', '', $packageName);
                    $packageName = ucfirst($packageName);

                    if ($packageName) {
                        $label .= ' ('.$packageName.')';
                    }
                } elseif (count($namespaceParts) > 0) {
                    // Single segment: same cleanup
                    $lastPart = end($namespaceParts);
                    if ($lastPart && $lastPart !== 'App') {
                        $lastPart = str_replace('Filament', '', $lastPart);
                        $lastPart = ucfirst($lastPart);
                        if ($lastPart) {
                            $label .= ' ('.$lastPart.')';
                        }
                    }
                }
            }

            $options[$modelClass] = $label;
        }

        return $options;
    }

    /**
     * Decide if a model class should be hidden from the Model Integration picker.
     *
     * @param  array<int, class-string>  $explicitExcluded
     */
    protected static function isExcludedModelClass(string $modelClass, array $explicitExcluded = []): bool
    {
        if ($modelClass === '' || ! class_exists($modelClass)) {
            return true;
        }

        // Always hide Voodflow internal models from the picker.
        if (str_starts_with($modelClass, 'Voodflow\\Voodflow\\')) {
            return true;
        }

        // Also hide the core Voodflow models configured by the package.
        $coreModels = array_values((array) config('voodflow.models', []));
        if (in_array($modelClass, $coreModels, true)) {
            return true;
        }

        // Explicit exclusions from config.
        return in_array($modelClass, $explicitExcluded, true);
    }
}
