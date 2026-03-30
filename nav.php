<style>
    /* Bottom Navigation */
    .bottom-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(135deg, #FFFFFF 0%, #FFF5FA 100%);
        border-top: 2px solid #FFD1E8;
        display: flex;
        justify-content: space-between;
        padding: 0.8rem 1.5rem;
        gap: 0.8rem;
        box-shadow: 0 -4px 20px rgba(255, 105, 180, 0.2);
        z-index: 9999;
        backdrop-filter: blur(10px);
    }

    .bottom-nav a {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.4rem;
        text-decoration: none;
        color: #D291BC;
        font-size: 0.8rem;
        font-weight: 700;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        flex: 1;
        padding: 0.7rem 0.5rem;
        border-radius: 12px;
        position: relative;
        letter-spacing: 0.5px;
    }

    .bottom-nav a::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255, 182, 217, 0.3) 0%, rgba(255, 105, 180, 0.1) 100%);
        border-radius: 12px;
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: -1;
    }

    .bottom-nav a:hover {
        color: #FF69B4;
        transform: translateY(-6px) scale(1.1);
    }

    .bottom-nav a:hover::before {
        opacity: 1;
    }

    .bottom-nav a.active {
        color: #FF1493;
        transform: scale(1.15);
    }

    .bottom-nav a.active::before {
        opacity: 1;
    }

    .nav-icon {
        font-size: 1.8rem;
        line-height: 1;
        transition: all 0.3s ease;
        filter: drop-shadow(0 0 3px rgba(255, 105, 180, 0.4));
    }

    .bottom-nav a:hover .nav-icon {
        filter: drop-shadow(0 0 8px rgba(255, 20, 147, 0.6));
        animation: bobble 0.6s ease-in-out infinite;
    }

    @keyframes bobble {
        0%, 100% { transform: translateY(0); }
        25% { transform: translateY(-4px); }
        50% { transform: translateY(0); }
        75% { transform: translateY(-2px); }
    }

    /* Mobile responsive */
    @media (max-width: 600px) {
        .bottom-nav {
            padding: 0.6rem 1rem;
            gap: 0.4rem;
        }

        .bottom-nav a {
            font-size: 0.7rem;
            padding: 0.5rem;
        }

        .nav-icon {
            font-size: 1.2rem;
        }
    }
</style>

<!-- Bottom Navigation -->
<nav class="bottom-nav">
    <a href="home.php" title="Home" class="<?php echo basename($_SERVER['PHP_SELF']) === 'home.php' ? 'active' : ''; ?>">
        <span class="nav-icon">♡</span>
        <span>Home</span>
    </a>
    <a href="create.php" title="Write" class="<?php echo basename($_SERVER['PHP_SELF']) === 'create.php' ? 'active' : ''; ?>">
        <span class="nav-icon">✦</span>
        <span>Write</span>
    </a>
    <a href="explore.php" title="Explore" class="<?php echo basename($_SERVER['PHP_SELF']) === 'explore.php' ? 'active' : ''; ?>">
        <span class="nav-icon">◎</span>
        <span>Explore</span>
    </a>
    <a href="share.php" title="My Thoughts" class="<?php echo basename($_SERVER['PHP_SELF']) === 'share.php' ? 'active' : ''; ?>">
        <span class="nav-icon">✧</span>
        <span>My Thoughts</span>
    </a>
</nav>
