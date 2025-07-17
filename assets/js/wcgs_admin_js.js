/***** enable sub product only for simple product ******/
jQuery(document).on("click", "#authenticate", function() {
  window.open(jQuery(this).attr("data-url"), "childWindow", "width=1000,height=700");
});