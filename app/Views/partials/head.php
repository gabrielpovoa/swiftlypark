<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            lucide.createIcons();
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .swal2-container.swal2-backdrop-show {
            background: rgba(5, 8, 13, 0.86) !important;
            backdrop-filter: blur(6px);
        }

        .swal2-popup {
            background: #131720 !important;
            color: #e2e8f0 !important;
            border: 1px solid rgba(255, 255, 255, 0.09);
            border-radius: 1.75rem !important;
            box-shadow: 0 28px 80px rgba(0, 0, 0, 0.55) !important;
        }

        .swal2-title { color: #f8fafc !important; font-weight: 900 !important; }
        .swal2-html-container { color: #94a3b8 !important; }
        .swal2-input-label { color: #94a3b8 !important; font-weight: 800; }

        .swal2-input,
        .swal2-select,
        .swal2-textarea {
            background: #0b0e14 !important;
            border: 1px solid rgba(255, 255, 255, 0.10) !important;
            color: #f8fafc !important;
            border-radius: 0.9rem !important;
            box-shadow: none !important;
        }

        .swal2-input:focus,
        .swal2-select:focus,
        .swal2-textarea:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18) !important;
        }

        .swal2-validation-message {
            background: rgba(244, 63, 94, 0.10) !important;
            color: #fb7185 !important;
            border-radius: 0.9rem;
        }

        .swal2-confirm,
        .swal2-cancel {
            border-radius: 0.85rem !important;
            padding: 0.8rem 1.4rem !important;
            font-weight: 900 !important;
        }

        .swal2-close { color: #64748b !important; }
    </style>
    <script>
        window.Swal = Swal.mixin({
            background: '#131720',
            color: '#e2e8f0',
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#334155',
            reverseButtons: true
        });
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">

    <title><?= isset($title) ? $title : 'SwiftlyPark' ?></title>
</head>
<body style='font-family: "Nunito", sans-serif;'>
