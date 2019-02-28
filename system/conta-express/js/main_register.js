(function($) {

    $(".toggle-password").click(function() {

        $(this).toggleClass("zmdi-eye zmdi-eye-off");
        var input = $($(this).attr("toggle"));
        if (input.attr("type") == "password") {
            input.attr("type", "text");
        } else {
            input.attr("type", "password");
        }
    });

    $("#registroForm").submit(function(e) {
        e.preventDefault();
        detector();
    });

})(jQuery);

function detector() {
    var pass = $("#password").val();
    var re_pass = $("#re_password").val();
    if (pass != re_pass) {
        swal('Error!', 'Las contraseñas no son iguales', 'error');
        return
    }
    var formData = new FormData(document.getElementById("registroForm"));
    $.ajax({
        url: "../../public/api/usuarios/new",
        type: "POST",
        dataType: "html",
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        beforeSend: function() {},
        success: function(res) {
            rest = JSON.parse(res);
            console.log(rest);
            if (rest.status === true) {
                swal('Usuario creado', 'El usuario se creo correctamente', 'success');
                $("#registroForm")[0].reset();
            } else {
                swal('Error', rest.error, 'error');
            }
            // swal('Usuario creado', 'El usuario se creo correctamente','success');
        }
    });
}