</div>
        </main>
    </div>
    
    <footer class="bg-white border-t mt-auto">
        <div class="container mx-auto px-4 py-3">
            <div class="flex flex-col md:flex-row justify-between items-center text-sm text-gray-600">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. כל הזכויות שמורות.</p>
                <div class="flex mt-2 md:mt-0">
                    <a href="#" class="ml-4 hover:text-purple-600">תנאי שימוש</a>
                    <a href="#" class="ml-4 hover:text-purple-600">מדיניות פרטיות</a>
                    <a href="#" class="hover:text-purple-600">עזרה ותמיכה</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Custom Scripts -->
    <script>
        $(document).ready(function() {
            // Tooltips
            $('[data-tooltip]').hover(
                function() {
                    var tooltip = $('<div class="tooltip bg-gray-800 text-white text-xs rounded px-2 py-1 absolute z-10"></div>');
                    tooltip.text($(this).data('tooltip'));
                    $('body').append(tooltip);
                    
                    var position = $(this).position();
                    tooltip.css({
                        top: position.top - tooltip.outerHeight() - 5,
                        left: position.left + ($(this).outerWidth() / 2) - (tooltip.outerWidth() / 2)
                    });
                },
                function() {
                    $('.tooltip').remove();
                }
            );
            
            // Confirmation dialogs
            $('[data-confirm]').on('click', function(e) {
                e.preventDefault();
                
                var message = $(this).data('confirm') || 'האם אתה בטוח שברצונך לבצע פעולה זו?';
                var href = $(this).attr('href');
                
                if (confirm(message)) {
                    window.location.href = href;
                }
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert-dismissible').fadeOut('slow');
            }, 5000);
            
            // Toggle mobile menu
            $('#mobile-menu-button').on('click', function() {
                $('#mobile-menu').toggleClass('hidden');
            });
        });
    </script>
</body>
</html>