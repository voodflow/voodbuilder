<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs\Bindings;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Voodflow\Voodbuilder\Models\ModelIntegration;

/**
 * Resolves fields from the currently authenticated user when the integration
 * model is Authenticatable (typical use case: "Ciao {name}", profile links).
 *
 * Distinct from `{alias}.latest`, which is the newest DB record — not the session user.
 */
final class ModelIntegrationAuthBindingSource extends AbstractModelIntegrationBindingSource
{
    public static function supports(ModelIntegration $integration): bool
    {
        $class = $integration->model_class;

        if (! is_string($class) || $class === '' || ! class_exists($class)) {
            return false;
        }

        if (! is_subclass_of($class, Model::class)) {
            return false;
        }

        return is_subclass_of($class, Authenticatable::class);
    }

    public function id(): string
    {
        return $this->integration->getAlias().'.auth';
    }

    public function label(): string
    {
        // Bricks-style standalone group: "User profile" / "Profilo utente"
        return __('voodbuilder::model_integrations.bindings.auth');
    }

    public function fields(): array
    {
        return $this->fieldsForEssentialKeys();
    }

    public function resolve(string $fieldId, BindingContext $context): ?string
    {
        $user = auth()->user();

        if (! $user instanceof Model) {
            return null;
        }

        $expected = $this->integration->model_class;

        if (! is_a($user, $expected)) {
            return null;
        }

        $eagerLoads = $this->relationEagerLoads();

        if ($eagerLoads !== []) {
            $user->loadMissing($eagerLoads);
        }

        return $this->resolveFieldValue($user, $fieldId, $context);
    }
}
