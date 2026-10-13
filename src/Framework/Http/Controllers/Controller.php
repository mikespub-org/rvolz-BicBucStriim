<?php

declare(strict_types=1);

namespace BicBucStriim\Framework\Http\Controllers;

/**
 * Framework-integration base controller.
 *
 * A minimal abstract base for the framework-integration controllers in this
 * namespace (currently the Laravel {@see ActionAdapterController}). It mirrors
 * the empty base-controller convention used by Laravel itself
 * (`App\Http\Controllers\Controller`), giving the adapter a concrete parent in
 * its own namespace without pulling in any framework-specific behaviour.
 *
 * It intentionally exposes no methods: the shared action classes carry all the
 * application logic, and each adapter controller wires that logic into its host
 * framework's request lifecycle. Subclasses may add shared controller helpers
 * here in the future if one becomes common across integrations.
 */
abstract class Controller
{
    //
}
