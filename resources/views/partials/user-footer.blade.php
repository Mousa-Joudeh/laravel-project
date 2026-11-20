<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a href="{{ url('/') }}" class="brand">
                    <svg class="brand-mark" viewBox="0 0 32 32" fill="none">
                        <rect x="2" y="8" width="28" height="20" rx="6" fill="#141413"/>
                        <circle cx="16" cy="17" r="6.6" fill="#fff"/>
                        <circle cx="16" cy="17" r="4.4" fill="#ff5b1a"/>
                    </svg>
                    <span>SPACES</span>
                </a>
                <p class="muted footer-text">Studios, creative spaces and photo packages across Jordan, booked in a few clicks.</p>
                <a href="tel:+96265550199" class="footer-phone"><i class="bi bi-telephone"></i> +962 6 555 0199</a>
            </div>

            <div>
                <h5>Explore</h5>
                <ul>
                    <li><a href="{{ route('studios.index') }}">All spaces</a></li>
                    <li><a href="{{ route('studiotypes.index') }}">Studio types</a></li>
                    <li><a href="{{ route('my-bookings.index') }}">My bookings</a></li>
                </ul>
            </div>

            <div>
                <h5>Account</h5>
                <ul>
                    @guest
                        <li><a href="{{ route('login') }}">Login</a></li>
                        <li><a href="{{ route('register') }}">Sign up</a></li>
                    @else
                        <li><a href="{{ route('reviews.index') }}">My reviews</a></li>
                    @endguest
                </ul>
            </div>

            <div>
                <h5>Contact</h5>
                <ul>
                    <li><a href="mailto:hello@spaces.jo">hello@spaces.jo</a></li>
                    <li>Amman, Jordan</li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; {{ date('Y') }} SPACES. All rights reserved.</span>
            <span>AMMAN · IRBID · ZARQA · AQABA · SALT</span>
        </div>
    </div>
</footer>