<?php $this->partial('head', ['title' => $title]); ?>
<?php $this->partial('header'); ?>

<main class="overflow-hidden flex items-start justify-start w-5/6 shadow-md bg-[#485696] px-8 py-6 ml-auto min-h-screen">
    <?= $content ?? '' ?>
</main>



<?php $this->partial('footer'); ?>
