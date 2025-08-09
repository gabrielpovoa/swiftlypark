<?php $this->partial('head', ['title' => $title]); ?>

<section class="flex items-start justify-start w-5/6 shadow-md bg-[#485696] px-8 py-6 ml-auto">
<h2 class="text-white text-2xl mb-6 font-semibold">Login</h2>

<?php if (!empty($error)) : ?>
    <p class="text-red-400 mb-4"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form action="/login/authenticate" method="POST" class="space-y-4">
    <label for="email" class="block text-white font-medium">Email</label>
    <input type="email" id="email" name="email" required
           class="w-full px-4 py-2 rounded bg-[#364574] text-white" placeholder="seu@email.com">

    <label for="password" class="block text-white font-medium">Senha</label>
    <input type="password" id="password" name="password" required
           class="w-full px-4 py-2 rounded bg-[#364574] text-white" placeholder="••••••••">

    <button type="submit" class="w-full py-3 bg-blue-700 hover:bg-blue-800 rounded font-semibold text-white">Entrar</button>
</form>

</section>
<?php $this->partial('footer'); ?>
