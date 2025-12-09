    </div> <!-- Close container-fluid -->
</div> <!-- Close main-content -->

<script src="/zaina-beauty/assets/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle Sidebar
document.getElementById('toggleSidebar').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('collapsed');
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fix any incorrect initial states
    const stockSubmenu = document.getElementById('stockSubmenu');
    const stockToggle = document.querySelector('[data-bs-target="#stockSubmenu"]');
    
    if (stockSubmenu && stockToggle) {
        const isOnStockPage = window.location.pathname.includes('/stock/');
        const hasShowClass = stockSubmenu.classList.contains('show');
        
        // Ensure consistent state
        if (isOnStockPage && !hasShowClass) {
            stockSubmenu.classList.add('show');
            stockToggle.setAttribute('aria-expanded', 'true');
            // Rotate arrow
            const arrow = stockToggle.querySelector('.toggle-arrow');
            if (arrow) arrow.style.transform = 'rotate(180deg)';
        } else if (!isOnStockPage && hasShowClass) {
            stockSubmenu.classList.remove('show');
            stockToggle.setAttribute('aria-expanded', 'false');
        }
    }
});
</script>

</body>
</html>