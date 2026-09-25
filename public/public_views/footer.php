<?php
/**
 * Shared variable created by Support\ViewResponse::render().
 *
 * @var string $__corianderRequestedView Requested view path.
 */
?>
    </main>
    <!-- Site footer -->
    <footer id="footer" class="w-full border-t border-slate/15 bg-true-white">
        <div class="mx-auto flex w-full max-w-7xl items-center justify-center px-4 py-5 text-sm text-slate sm:px-6 lg:px-8">
            <span>Sans Suite</span>
        </div>
    </footer>
    <!-- Shared and view-specific scripts -->
    <script type="module" src="<?= \CorianderCore\Core\Support\PublicUrl::versionedAsset('assets/js/app/index.js') ?>" defer></script>
    <?php
    $requestedView = isset($__corianderRequestedView) ? $__corianderRequestedView : 'home';
    if (file_exists(PROJECT_ROOT . '/public/assets/js/' . $requestedView . '/index.js')):
    ?>
        <script type="module" src="<?= \CorianderCore\Core\Support\PublicUrl::versionedAsset('assets/js/' . $requestedView . '/index.js') ?>" defer></script>
    <?php endif; ?>
</body>
</html>
