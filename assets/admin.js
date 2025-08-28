jQuery(function($){
function renumber(){
$('#whitestudioteam-wcpa-rows tr').each(function(i){
$(this).find('input, select, textarea').each(function(){
this.name = this.name.replace(/wcpa\[[^\]]+\]/, 'wcpa['+i+']');
});
});
}
$('#whitestudioteam-wcpa-add-row').on('click', function(){
var html = $('#tmpl-whitestudioteam-wcpa-row').html().replace(/\{\{INDEX\}\}/g, $('#whitestudioteam-wcpa-rows tr').length);
$('#whitestudioteam-wcpa-rows').append(html);
});
$(document).on('click', '.whitestudioteam-wcpa-remove', function(){
$(this).closest('tr').remove();
renumber();
});
});