<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

use Voodflow\Voodbuilder\Licensing\Contracts\EntitlementProvider;

/**
 * Mutable provider for PHPUnit — simulate plan matrices without AnyStack.
 */
final class TestingEntitlementProvider implements EntitlementProvider
{
    private CapabilitySet $capabilities;

    private LicenceStatus $status;

    /**
     * @param  list<string>|null  $capabilities
     */
    public function __construct(?array $capabilities = null, ?LicenceStatus $status = null)
    {
        $this->capabilities = CapabilitySet::from($capabilities ?? EditionCapabilityMatrix::agency());
        $this->status = $status ?? new LicenceStatus(
            edition: EditionCapabilityMatrix::EDITION_AGENCY,
            active: true,
            identifier: 'testing',
        );
    }

    public static function forEdition(string $edition): self
    {
        return new self(
            EditionCapabilityMatrix::forEdition($edition),
            new LicenceStatus(
                edition: $edition,
                active: true,
                identifier: 'testing:'.$edition,
            ),
        );
    }

    /**
     * @param  list<string>  $capabilities
     */
    public function grant(array $capabilities): void
    {
        $this->capabilities = $this->capabilities->merge(CapabilitySet::from($capabilities));
    }

    /**
     * @param  list<string>  $capabilities
     */
    public function replace(array $capabilities): void
    {
        $this->capabilities = CapabilitySet::from($capabilities);
    }

    public function setStatus(LicenceStatus $status): void
    {
        $this->status = $status;
    }

    public function capabilities(): CapabilitySet
    {
        return $this->capabilities;
    }

    public function licenceStatus(): LicenceStatus
    {
        return $this->status;
    }
}
