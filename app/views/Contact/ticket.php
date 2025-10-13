<?php $this->partial('head', ['title' => $title]); ?>

<body style="margin:0; padding:0; font-family:Arial, Helvetica, sans-serif; background-color:#f4f6f8; color:#333;">

<!-- Container principal -->
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr>
        <td align="center" style="padding:20px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                   style="max-width:600px; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,0.1);">

                <!-- Cabeçalho -->
                <tr>
                    <td align="center" style="background-color:#3C4F76; padding:24px;">
                        <h1 style="margin:0; font-size:22px; color:#ffffff; font-weight:600;">
                            <?= htmlspecialchars($title) ?>
                        </h1>
                    </td>
                </tr>

                <!-- Conteúdo -->
                <tr>
                    <td style="padding:24px; color:#555; font-size:16px; line-height:1.6;">

                        <p style="margin:0 0 16px 0;">
                            Um cliente abriu um novo ticket de suporte. Seguem os detalhes:
                        </p>

                        <?php if (!empty($clientName)): ?>
                            <p style="margin:0 0 8px 0; font-size:15px; color:#333;">
                                <div style="font-weight: bold">Cliente:</div> <?= htmlspecialchars($clientName) ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($message)): ?>
                            <p style="margin:0 0 8px 0; font-size:15px; color:#333;">
                                <strong>Mensagem enviada pelo cliente:</strong>
                            </p>
                            <blockquote style="margin:12px 0; padding:12px; background:#f4f6f8; border-left:4px solid #3C4F76; border-radius:6px; font-size:15px; color:#333;">
                                <?= nl2br(htmlspecialchars($message)) ?>
                            </blockquote>
                        <?php endif; ?>

                        <?php if (!empty($ticketNumber)): ?>
                            <p style="margin:16px 0; font-size:16px; font-weight:bold; text-align:center;">
                                Ticket gerado:
                                <span style="display:inline-block; margin-top:8px; background:#3C4F76; color:#fff; padding:8px 16px; border-radius:8px; font-size:18px;">
                                    <?= htmlspecialchars($ticketNumber) ?>
                                </span>
                            </p>
                        <?php endif; ?>

                        <p style="margin-top:20px; font-size:14px; color:#777;">
                            ➡️ A equipe de suporte deve acompanhar e dar continuidade a este atendimento.
                        </p>
                    </td>
                </tr>

                <!-- Rodapé -->
                <tr>
                    <td align="center" style="background:#f4f6f8; padding:20px; font-size:14px; color:#888; border-top:1px solid #ddd;">
                        <p style="margin:0 0 12px 0;">SwiftlyPark &copy; <?= date('Y') ?></p>

                        <p style="margin:0;">
                            <a href="https://joao-povoa-filho.vercel.app/" target="_blank" rel="noopener noreferrer"
                               style="color:#3C4F76; text-decoration:none; font-weight:bold; margin:0 10px;">
                                🌐 Portfólio
                            </a>
                            <a href="https://www.linkedin.com/in/gabriel-limapovoa/" target="_blank" rel="noopener noreferrer"
                               style="color:#3C4F76; text-decoration:none; font-weight:bold; margin:0 10px;">
                                💼 LinkedIn
                            </a>
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
