<style>
    @import url('https://fonts.googleapis.com/css2?family=Caveat:wght@400;700&family=Indie+Flower&display=swap');

    /* Header */
    header {
        background: linear-gradient(135deg, #FFB6D9 0%, #FF91C5 50%, #FF69B4 100%);
        padding: 2.5rem 1rem;
        text-align: center;
        border-bottom: 2px dashed #FF69B4;
        box-shadow: 0 8px 30px rgba(255, 105, 180, 0.35);
        position: relative;
        overflow: hidden;
    }

    header::before {
        content: '★';
        position: absolute;
        top: 10px;
        left: 20px;
        font-size: 1.5rem;
        opacity: 0.4;
        animation: float 4s ease-in-out infinite;
    }

    header::after {
        content: '◈';
        position: absolute;
        top: 10px;
        right: 20px;
        font-size: 1.5rem;
        opacity: 0.4;
        animation: float 4s ease-in-out infinite reverse;
    }

    header h1 {
        font-size: 2.8rem;
        color: white;
        font-weight: 700;
        letter-spacing: 0.5px;
        margin-bottom: 0.3rem;
        text-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        position: relative;
        z-index: 1;
        font-family: 'Caveat', cursive;
        font-style: normal;
    }

    header p {
        color: rgba(255, 255, 255, 0.95);
        font-size: 0.9rem;
        font-style: italic;
        font-weight: 500;
        position: relative;
        z-index: 1;
        text-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-12px); }
    }

    /* Responsive */
    @media (max-width: 480px) {
        header {
            padding: 2rem 1rem;
        }

        header h1 {
            font-size: 1.8rem;
        }

        header::before,
        header::after {
            font-size: 1.2rem;
        }
    }
</style>

<header>
    <h1><?php echo isset($header_title) ? $header_title : '⊹ Unsaid Thoughts'; ?></h1>
    <p><?php echo isset($header_subtitle) ? $header_subtitle : 'Everything you never said'; ?></p>
</header>
