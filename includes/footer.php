<?php
// includes/footer.php
?>
    </div> </main> <footer class="mt-auto bg-dark text-white-50 py-3">
    <div class="container text-center">
        <small>© 2568 ระบบแจ้งของหาย มหาวิทยาลัย. พัฒนาด้วย PHP & Bootstrap 5.</small>
    </div>
</footer>

<script src="<?= BASE_URL; ?>assets/js/bootstrap.bundle.min.js"></script>

</body>
</html>
<?php
// ปิดการเชื่อมต่อฐานข้อมูลเมื่อจบรอบการทำงานของสคริปต์
$conn->close();
?>