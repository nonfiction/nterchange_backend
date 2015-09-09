$(function(){

  $('[data-href]').click(function(){
    window.location = $(this).data('href');
  });

  $('a[href*=reorder_content]').each(function(){
    var href = $(this).attr('href');
    $(this).click(function(){
      window.open(href, 'sort', 'width=500,height=550,resizable,scrollbars');
      return false;
    });
  });

});
