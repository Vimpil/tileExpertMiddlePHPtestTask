<?php

// This file has been auto-generated by the Symfony Dependency Injection Component for internal use.

if (\class_exists(\ContainerGDxhuju\App_KernelDevDebugContainer::class, false)) {
    // no-op
} elseif (!include __DIR__.'/ContainerGDxhuju/App_KernelDevDebugContainer.php') {
    touch(__DIR__.'/ContainerGDxhuju.legacy');

    return;
}

if (!\class_exists(App_KernelDevDebugContainer::class, false)) {
    \class_alias(\ContainerGDxhuju\App_KernelDevDebugContainer::class, App_KernelDevDebugContainer::class, false);
}

return new \ContainerGDxhuju\App_KernelDevDebugContainer([
    'container.build_hash' => 'GDxhuju',
    'container.build_id' => '1d6f8ffe',
    'container.build_time' => 1747832212,
    'container.runtime_mode' => \in_array(\PHP_SAPI, ['cli', 'phpdbg', 'embed'], true) ? 'web=0' : 'web=1',
], __DIR__.\DIRECTORY_SEPARATOR.'ContainerGDxhuju');
