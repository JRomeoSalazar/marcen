$(document).on("ready", function () {
  $("span.contrareembolso").hover(
    function() {
      var alter = $(this).data('alter');
      $(this).html(alter);

      $(this).removeClass("label-primary");
      $(this).addClass("label-success");
    },
    function() {
      var txt = $(this).data('txt');
      $(this).html(txt);
      
      $(this).removeClass("label-success");
      $(this).addClass("label-primary");
    }
  );
  $("span.transferencia").hover(
    function() {
      var alter = $(this).data('alter');
      $(this).html(alter);

      $(this).removeClass("label-warning");
      $(this).addClass("label-success");
    },
    function() {
      var txt = $(this).data('txt');
      $(this).html(txt);
      
      $(this).removeClass("label-success");
      $(this).addClass("label-warning");
    }
  );
});