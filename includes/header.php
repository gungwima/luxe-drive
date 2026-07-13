<?php
// ============================================================
// includes/header.php
// Bagian <head> HTML untuk semua halaman USER
// Cara pakai: require_once '../includes/header.php';
//
// Variabel opsional yang bisa di-set sebelum require:
// $page_title    = 'Judul Halaman';   (default: APP_NAME)
// $page_active   = 'beranda';         (untuk highlight menu aktif)
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';

$page_title  = isset($page_title)  ? $page_title . ' — ' . APP_NAME : APP_NAME;
$page_active = $page_active ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <!-- Tailwind Config (sesuai design system Luxe Drive) -->
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary":                    "#041627",
                        "on-primary":                 "#ffffff",
                        "primary-container":          "#1a2b3c",
                        "on-primary-container":       "#8192a7",
                        "primary-fixed":              "#d2e4fb",
                        "primary-fixed-dim":          "#b7c8de",
                        "on-primary-fixed":           "#0b1d2d",
                        "on-primary-fixed-variant":   "#38485a",
                        "secondary":                  "#855300",
                        "on-secondary":               "#ffffff",
                        "secondary-container":        "#fea619",
                        "on-secondary-container":     "#684000",
                        "secondary-fixed":            "#ffddb8",
                        "secondary-fixed-dim":        "#ffb95f",
                        "on-secondary-fixed":         "#2a1700",
                        "on-secondary-fixed-variant": "#653e00",
                        "tertiary":                   "#0b1624",
                        "on-tertiary":                "#ffffff",
                        "tertiary-container":         "#202a39",
                        "on-tertiary-container":      "#8791a4",
                        "tertiary-fixed":             "#d9e3f7",
                        "tertiary-fixed-dim":         "#bdc7db",
                        "on-tertiary-fixed":          "#121c2a",
                        "on-tertiary-fixed-variant":  "#3d4757",
                        "background":                 "#f8f9fa",
                        "on-background":              "#191c1d",
                        "surface":                    "#f8f9fa",
                        "on-surface":                 "#191c1d",
                        "surface-variant":            "#e1e3e4",
                        "on-surface-variant":         "#44474c",
                        "surface-bright":             "#f8f9fa",
                        "surface-dim":                "#d9dadb",
                        "surface-container-lowest":   "#ffffff",
                        "surface-container-low":      "#f3f4f5",
                        "surface-container":          "#edeeef",
                        "surface-container-high":     "#e7e8e9",
                        "surface-container-highest":  "#e1e3e4",
                        "inverse-surface":            "#2e3132",
                        "inverse-on-surface":         "#f0f1f2",
                        "inverse-primary":            "#b7c8de",
                        "surface-tint":               "#4f6073",
                        "outline":                    "#74777d",
                        "outline-variant":            "#c4c6cd",
                        "error":                      "#ba1a1a",
                        "on-error":                   "#ffffff",
                        "error-container":            "#ffdad6",
                        "on-error-container":         "#93000a",
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg":      "0.5rem",
                        "xl":      "0.75rem",
                        "full":    "9999px",
                    },
                    spacing: {
                        "base":           "8px",
                        "gutter":         "24px",
                        "margin-mobile":  "16px",
                        "margin-desktop": "64px",
                        "container-max":  "1280px",
                    },
                    fontFamily: {
                        "headline-md":       ["Montserrat"],
                        "headline-sm":       ["Montserrat"],
                        "display-lg":        ["Montserrat"],
                        "display-lg-mobile": ["Montserrat"],
                        "body-lg":           ["Inter"],
                        "body-md":           ["Inter"],
                        "label-md":          ["Inter"],
                        "label-sm":          ["Inter"],
                    },
                    fontSize: {
                        "display-lg":        ["48px", { lineHeight: "56px",  letterSpacing: "-0.02em", fontWeight: "700" }],
                        "display-lg-mobile": ["32px", { lineHeight: "40px",  letterSpacing: "-0.01em", fontWeight: "700" }],
                        "headline-md":       ["24px", { lineHeight: "32px",  fontWeight: "600" }],
                        "headline-sm":       ["20px", { lineHeight: "28px",  fontWeight: "600" }],
                        "body-lg":           ["18px", { lineHeight: "28px",  fontWeight: "400" }],
                        "body-md":           ["16px", { lineHeight: "24px",  fontWeight: "400" }],
                        "label-md":          ["14px", { lineHeight: "20px",  letterSpacing: "0.01em", fontWeight: "500" }],
                        "label-sm":          ["12px", { lineHeight: "16px",  fontWeight: "600" }],
                    },
                }
            }
        }
    </script>

    <style>
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            -webkit-font-smoothing: antialiased;
        }
        .ambient-shadow {
            box-shadow: 0px 4px 20px rgba(4, 22, 39, 0.08);
        }
        /* Smooth scroll */
        html { scroll-behavior: smooth; }
        /* Animasi fade in */
        .fade-in { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-background text-on-background font-body-md text-body-md antialiased">
