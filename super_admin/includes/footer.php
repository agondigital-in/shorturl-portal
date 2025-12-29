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
        // Sidebar Toggle for Mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        });
        
        document.getElementById('sidebarOverlay')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('show');
            this.classList.remove('show');
        });
        
        // Auto-hide alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(function(alert) {
            setTimeout(function() {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() { alert.remove(); }, 500);
            }, 5000);
        });
    </script>
</body>
</html>
