document.addEventListener('DOMContentLoaded', function() {
    var themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) return;

    themeToggle.addEventListener('click', function() {
        var currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        var newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        // Also update the meta tag if present
        var metaColorScheme = document.querySelector('meta[name="color-scheme"]');
        if (metaColorScheme) {
            metaColorScheme.content = newTheme;
        }
    });
});
