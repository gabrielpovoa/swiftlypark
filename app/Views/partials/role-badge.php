<?php

$roleLabel = htmlspecialchars(
    (string) ($roleMetadata['label'] ?? 'Sem papel'),
    ENT_QUOTES,
    'UTF-8'
);
$roleIcon = htmlspecialchars(
    (string) ($roleMetadata['icon'] ?? 'user'),
    ENT_QUOTES,
    'UTF-8'
);
?>

<span class="inline-flex items-center gap-1 text-[8px] text-blue-400 font-black uppercase tracking-widest">
    <i data-lucide="<?= $roleIcon ?>" class="w-3 h-3"></i>
    <?= $roleLabel ?>
</span>
