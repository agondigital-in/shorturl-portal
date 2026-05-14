        </div><!-- End content-wrapper -->
    </main><!-- End main-content -->
    
    <!-- Footer -->
    <footer class="main-content" style="padding: 0;">
        <div class="content-wrapper pt-0">
            <div class="card mt-4" style="border-radius: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div class="text-white small">
                            &copy; <?php echo date('Y'); ?> <strong>Ads Platform</strong>. All rights reserved.
                        </div>
                        <div class="text-white small">
                            <i class="fas fa-code me-1"></i> Version 2.0 | Made with <i class="fas fa-heart text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Theme Switcher
        const themeButtons = document.querySelectorAll('.theme-btn');
        const body = document.body;
        
        // Load saved theme from localStorage
        const savedTheme = localStorage.getItem('theme') || 'light';
        applyTheme(savedTheme);
        
        themeButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const theme = this.getAttribute('data-theme');
                applyTheme(theme);
                localStorage.setItem('theme', theme);
            });
        });
        
        function applyTheme(theme) {
            // Remove all theme classes
            body.classList.remove('dark-theme', 'eye-protection-theme');
            
            // Remove active class from all buttons
            themeButtons.forEach(btn => btn.classList.remove('active'));
            
            // Apply selected theme
            if (theme === 'dark') {
                body.classList.add('dark-theme');
            } else if (theme === 'eye-protection') {
                body.classList.add('eye-protection-theme');
            }
            
            // Set active button
            const activeBtn = document.querySelector(`.theme-btn[data-theme="${theme}"]`);
            if (activeBtn) {
                activeBtn.classList.add('active');
            }
        }
        
        // Desktop Sidebar Toggle
        const sidebarToggleDesktop = document.getElementById('sidebarToggleDesktop');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.querySelector('.main-content');
        const topNavbar = document.querySelector('.top-navbar');
        
        // Load saved sidebar state
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            topNavbar.classList.add('expanded');
        }
        
        if (sidebarToggleDesktop) {
            sidebarToggleDesktop.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                topNavbar.classList.toggle('expanded');
                
                // Save state
                const isCollapsed = sidebar.classList.contains('collapsed');
                localStorage.setItem('sidebarCollapsed', isCollapsed);
            });
        }
        
        // Mobile Sidebar Toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
                document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
            });
        }
        
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                this.classList.remove('show');
                document.body.style.overflow = '';
            });
        }
        
        // Close sidebar on link click (mobile)
        if (window.innerWidth <= 991.98) {
            document.querySelectorAll('.sidebar-link').forEach(function(link) {
                link.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                    document.body.style.overflow = '';
                });
            });
        }
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 991.98) {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
        
        // Auto-hide alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(function(alert) {
            setTimeout(function() {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() { alert.remove(); }, 500);
            }, 5000);
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>
