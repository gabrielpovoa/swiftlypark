<?php $this->partial('head', ['title' => $title]); ?>

<body style="margin:0; padding:0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color:#f0f2f5; color:#334155;">

<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#f0f2f5;">
    <tr>
        <td align="center" style="padding: 20px 10px;"> <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width:600px; background:#ffffff; border-radius:24px; overflow:hidden; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">

                <tr>
                    <td align="center" style="background-color:#0b0e14; padding:40px 20px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 15px;">
                            <tr>
                                <td align="center" style="background-color:#3b82f6; border-radius:12px; padding:12px 16px;">
                                    <span style="font-size:24px; font-weight:900; color:#ffffff; font-style: italic; line-height: 1;">P</span>
                                </td>
                            </tr>
                        </table>
                        <h1 style="margin:0; font-size:18px; color:#ffffff; font-weight:800; letter-spacing:1px; text-transform: uppercase; font-family: sans-serif;">
                            <?= htmlspecialchars($title) ?>
                        </h1>
                    </td>
                </tr>

                <tr>
                    <td style="padding:40px; font-size:16px; line-height:1.6;">

                        <p style="margin:0 0 24px 0; color:#64748b; font-weight: 500; text-align: center;">
                            Um novo chamado foi aberto por um usuário logado.
                        </p>

                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:24px;">
                            <tr>
                                <td style="padding:20px; background-color:#f8fafc; border-radius:20px; border:1px solid #f1f5f9; text-align: center;">
                                    <span style="display:block; font-size:11px; font-weight:800; color:#3b82f6; text-transform:uppercase; letter-spacing:1.5px; margin-bottom:6px;">Usuário Solicitante</span>
                                    <span style="font-size:18px; font-weight:800; color:#0f172a; display: block;"><?= htmlspecialchars($clientName ?? 'Visitante') ?></span>
                                    <?php if(isset($emailCliente)): ?>
                                        <span style="font-size:13px; color:#64748b;"><?= htmlspecialchars($emailCliente) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0 0 10px 0; font-size:13px; font-weight:800; color:#1e293b; text-transform: uppercase; letter-spacing: 0.5px;">
                            Descrição do Problema:
                        </p>
                        <div style="margin:0 0 32px 0; padding:24px; background-color:#ffffff; border-left:4px solid #3b82f6; border-radius:8px; font-size:15px; color:#334155; font-style: italic; background-color: #f8fafc; line-height: 1.8;">
                            "<?= nl2br(htmlspecialchars($message)) ?>"
                        </div>

                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                            <tr>
                                <td align="center" style="padding:30px; background-color:#0b0e14; border-radius:24px; text-align:center;">
                                    <span style="display:block; font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:2px; margin-bottom:12px;">Protocolo de Atendimento</span>
                                    <span style="font-family:'Courier New', Courier, monospace; font-size:24px; font-weight:bold; color:#3b82f6; letter-spacing:3px;">
                                        <?= htmlspecialchars($ticketNumber) ?>
                                    </span>
                                </td>
                            </tr>
                        </table>

                        <p style="margin-top:35px; font-size:12px; color:#94a3b8; text-align:center; line-height: 1.5;">
                            Ação necessária: Por favor, analise a solicitação e responda ao cliente através do painel de controle.
                        </p>
                    </td>
                </tr>

                <tr>
                    <td align="center" style="background-color:#f8fafc; padding:35px; border-top:1px solid #f1f5f9;">
                        <p style="margin:0 0 15px 0; font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:2px;">
                            SwiftlyPark System &copy; <?= date('Y') ?>
                        </p>
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="padding:0 12px;">
                                    <a href="https://joao-povoa-filho.vercel.app/" style="color:#3b82f6; text-decoration:none; font-size:12px; font-weight:bold; transition: all 0.3s;">Portfólio</a>
                                </td>
                                <td style="border-left: 1px solid #e2e8f0; padding-left: 12px; padding-right: 12px;">
                                    <a href="https://www.linkedin.com/in/gabriel-limapovoa/" style="color:#3b82f6; text-decoration:none; font-size:12px; font-weight:bold; transition: all 0.3s;">LinkedIn</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>