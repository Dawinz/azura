            </div>
        </main>
        <footer class="footer">
            <div class="footer-container d-flex flex-column justify-content-center align-items-center pt-3 mx-auto">
                <p class="copyright text-center text-secondary"><?php echo lang('copyrightText'); ?> &copy; 2021-<?php echo date('Y') ?> <strong>Rahisi Recharge</strong>. <?php echo lang('allRightsReservedText'); ?>.</p>
            </div>
        </footer>
    </div>

    <!-- Add your JavaScript code here -->
    <script>
        function submitForm() {
            // Get the selected provider value
            var provider = document.getElementById("provider").value;

            // Define the URL for different providers
            var urls = {
                "tigo": "http://localhost/aking/home/buy",
                "vodacom": "http://localhost/aking/home/buy",
                "airtel": "http://localhost/aking/home/buy",
                "halotel": "http://localhost/aking/home/buy",
                "ttcl": "http://localhost/aking/home/buy",
            };

            // Get the selected URL based on the provider
            var url = urls[provider];

            // Redirect to the selected URL
            if (url) {
                document.getElementById("electricity-form").action = url;
                document.getElementById("electricity-form").submit();
            } else {
                alert("Invalid provider selected.");
            }
        }
    </script>

    <!-- View Template Javascript -->
        <!-- Site JS -->
        <script type="text/javascript"></script>

    <!-- Base HTML Javascript -->
        <!-- Vendor JS -->
            <script type="text/javascript" src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
            <script type="text/javascript" src="https://code.jquery.com/jquery-migrate-3.4.1.min.js" crossorigin="anonymous"></script>
            <!-- Popper JS -->
            <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
            <!-- Bootstrap -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
            <!-- Ionicons -->
            <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
            <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
        
        <!-- Site JS -->
        <script type="text/javascript" src="<?= base_url('assets/js/init.js') ?>"></script>
        <script type="text/javascript"></script>
    <!-- End of Javascript -->
</body>
</html>
