    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Cargar secciones por AJAX en #main
$(document).on('click', '.nav-link[data-section]', function(e){
    e.preventDefault();
    let section = $(this).data('section');
    $("#main").html('<div class="text-center p-5 text-light">⏳ Cargando '+section+'...</div>');
    $(".nav-link").removeClass("active");
    $(this).addClass("active");
    $.get(section+".php", function(data){
        $("#main").html(data);
    }).fail(function(){
        $("#main").html('<div class="alert alert-danger">⚠️ Error cargando sección '+section+'</div>');
    });
});
</script>
</body>
</html>