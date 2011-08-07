$(document).ready(function(){
  $('#items').dblclick(function(){
    var id_menu = checkSelectedItem('edit');
    if (id_menu) {
      location.href = base_dir + '&edit&id_menu=' + id_menu;
    }
  });
  $('input.clone').keyup(function(){
    $(this).parent('div').siblings('div').children('input.cloneParent').removeClass('cloneParent');
  });
  $('input.cloneParent').keyup(function(){
    var val = $(this).val();
    if ($(this).hasClass('cloneParent')) {
      $(this).parent('div').siblings('div').children('input.clone').val(val);
    }
  });
  $('img.action').click(function(){
      var mode = $(this).attr('alt');
      var id_menu = checkSelectedItem(mode);
      if (id_menu) {
        location.href = base_dir + '&'+mode+'&id_menu=' + id_menu;
      }
  });
});

function checkSelectedItem(mode)
{
  var value = $('#items').val();

  if (value == null) {
    alert(txt_select_list);
    return false;
  }
  else {
    if (mode == 'delete') {
      if (confirm(txt_delete)) {
        return value;
      }
    }
    else {
      return value;
    }
    return false;
  }
}