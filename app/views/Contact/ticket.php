<?php $this->partial('head', ['title' => $title]); ?>

<body style="background-color:#383F51; font-family:sans-serif; color:#D1BEB0; margin:0; padding:0; line-height:1.5;">

<div style="background-color:#3C4F76; color:#ffffff; text-align:center; padding:24px 0; border-bottom-left-radius:12px; border-bottom-right-radius:12px;">
    <h2 style="margin:0; font-size:24px; font-weight:bold;"><?= $title ?></h2>
</div>

<div style="background-color:#D1BEB0; color:#383F51; border-radius:12px; padding:24px; margin:24px auto; max-width:600px; box-shadow:0 4px 12px rgba(0,0,0,0.2);">
    <p style="margin-bottom:16px; font-size:16px; line-height:1.6;"><?= $message ?></p>

    <?php if (!empty($ticketNumber)): ?>
        <p style="margin-top:16px; font-size:16px; font-weight:bold;">
            Seu ticket: <strong style="color:#3C4F76;"><?= $ticketNumber ?></strong>
        </p>
    <?php endif; ?>
</div>

<div style="text-align:center; font-size:14px; color:#AB9F9D; padding:24px; border-top:1px solid rgba(171, 159, 157, 0.3);">
    <p style="margin:0 0 16px 0;">SwiftlyPark &copy; <?= date('Y') ?></p>

    <div style="text-align:center;">
        <a href="https://joao-povoa-filho.vercel.app/" target="_blank" rel="noopener noreferrer" style="color:#AB9F9D; text-decoration:none; font-weight:bold; padding:0 12px;">
            Portfólio
        </a>
        <span style="color:#AB9F9D;">|</span>
        <a href="https://www.linkedin.com/in/gabriel-limapovoa/" target="_blank" rel="noopener noreferrer" style="color:#AB9F9D; text-decoration:none; font-weight:bold; padding:0 12px;">
            LinkedIn
        </a>
    </div>
</div>
</body>