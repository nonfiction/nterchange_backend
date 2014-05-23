###!
                                   ,...,,                  ,,
                                 .d' ""db           mm     db
                                 dM`                MM
`7MMpMMMb.  ,pW"Wq.`7MMpMMMb.   mMMmm`7MM  ,p6"bo mmMMmm `7MM  ,pW"Wq.`7MMpMMMb.
  MM    MM 6W'   `Wb MM    MM    MM    MM 6M'  OO   MM     MM 6W'   `Wb MM    MM
  MM    MM 8M     M8 MM    MM    MM    MM 8M        MM     MM 8M     M8 MM    MM
  MM    MM YA.   ,A9 MM    MM    MM    MM YM.    ,  MM     MM YA.   ,A9 MM    MM
.JMML  JMML.`Ybmd9'.JMML  JMML..JMML..JMML.YMbmd'   `Mbmo.JMML.`Ybmd9'.JMML  JMML.

##################################################################################

# Prepend yepnope.js for async resource loading
#//= require yepnope/yepnope
#//= require yepnope-pathfilter/yepnope.pathfilter

# These asset paths could potentially be replaced with a CDN
yepnope.paths =
  'components'   : "/nterchange/assets/components"
  'javascripts'  : "/nterchange/assets/javascripts"
  'stylesheets'  : "/nterchange/assets/stylesheets"

# Ensure we got jQuery
yepnope test: (window.jQuery), nope: [ 
  'components/jquery/jquery.min.js' 
], complete: -> 

  # Ensure we got jQuery UI
  yepnope test: (window.jQuery.ui), nope: [ 
    'javascripts/jquery-ui.js'
    'components/jquery-ui/themes/smoothness/jquery-ui.min.css'
  ], complete: -> window.n.init()

  # Ensure we got noty
  yepnope test: (window.jQuery.noty), nope: [ 
    'javascripts/noty.js'
  ], complete: -> window.n.init()

  # Ensure we got ckeditor
  yepnope test: (window.CKEDITOR), nope: [ 
    'components/ckeditor/ckeditor.js'
    'components/ckeditor/adapters/jquery.js'
  ], complete: -> 
    CKEDITOR.config.customConfig = '/javascripts/ckeditor_config.js'
    window.n.init()

# Breakpoints
window.sm = 768
window.md = 992
window.lg = 1200

# nonfiction methods
window.n = {}
n.count = 0

#
n.init = ->

  # Only init once all 3 yepnope components have been loaded
  n.count++
  return if n.count < 3

  $(window).resize -> n.breakpoints()
  n.breakpoints()
  # console.log('surf to edit!')
  n.surfToEdit()

#
n.grids = 
  'xs': ['sm', 'md', 'lg']
  'sm': ['md', 'lg']
  'md': ['lg']
  'lg': []

#
n.surfToEdit = ->

  # Get out of here unless in surf-to-edit mode
  return false unless location.href.match(/nterchange/g)

  # Classify HTML
  $('html').addClass('surf-to-edit')

  # Behaviour for all the toggles
  $('button.ntoolbar-toggle').each -> n.toolbar(this)

  # Behavior for the container buttons
  $('div.ntoolbar-page').each -> n.toolbarPage(this)

  # Every grid block
  $('.grid-block').each ->

    # Classify inital values
    n.gb.classify(this)

    # Col/Row chooser
    $(this).find('.ntoolbar .col-chooser a, .ntoolbar .row-chooser a').on 'click', (event) -> 
      event.preventDefault()
      $a = $(event.target)
      n.gb.grid($a, true)

    # Pull left/right
    $(this).find(".ntoolbar button.pull-left, .ntoolbar button.pull-right")
           .on 'click', (event) -> n.gb.pull(event, true)

    # Allow col/row resizing with drag/drop
    n.gb.resizable(this)


# 
n.columnToPercentage = (col) -> 
  "#{Math.round(((col / 12) * 100))}%"

#
n.id = (element) ->
  $element = $(element)
  unless $element.attr('id')
    id = [
      $element.prop("tagName").toLowerCase(), 
      $element.prop("className").split(' ').join('-'),
      new Date().getTime()
    ].join('-')
    $element.attr('id', id)
  $element.attr('id')

# 
n.cleanValue = (element) ->
  if $(element).data('value')
    value = $(element).data('value')
  else
    value = $(element).find('.dynamic')
                      .remove()
                      .end()
                      .html()
  value.replace(/[\n\r]/g, '').trim()

# 
n.cleanLabel = (element, count=2) ->
  return "" if count <= 1
  if $(element).data('label')
    value = $(element).data('label')
  else if $(element).data('field')
    value = $(element).data('field')
  else if $(element).data('type')
    value = $(element).data('type')
  else
    value = ""
  value.replace(/[\n\r]/g, '').trim()

# Breakpoint class names
n.breakpoints = ->
  offset = 15

  width = $(window).width() + offset
  $('html').removeClass('lg') if width < window.lg
  $('html').removeClass('md') if width < window.md
  $('html').removeClass('sm') if width < window.sm

  grid = 'xs'
  if width >= window.sm
    grid = 'sm'
    $('html').addClass(grid) 
  if width >= window.md
    grid = 'md'
    $('html').addClass(grid) 
  if width >= window.lg
    grid = 'lg'
    $('html').addClass(grid) 

  $('html').data('grid', grid)

  message = "Grid Size: <b>#{grid}</b>"
  $('.ntoolbar-main .grid-size').html(message)

  if location.href.match(/nterchange/g) and grid isnt window.grid
    noty layout: 'bottomCenter', type: 'information', timeout: 3000, text: message

  window.grid = grid

# Behaviour for the toggle
n.toolbar = (cog) ->
  $pageContent = $(cog).parent()
  $toolbar = $pageContent.find('.ntoolbar:first')

  if $pageContent.parent().hasClass('grid-block')
    $(cog).data('usingGrid', true)
  else
    $(cog).data('usingGrid', false)
    $pageContent.find('div[data-grid]').remove()

  # Toolbar Edit Mode
  $(cog).on 'edit', (event, editing) ->
    $asset = []

    if $(cog).data('usingGrid')
      $gb = $(event.currentTarget).closest('.grid-block')
      $asset = $gb.find('[data-asset]:first')
    $asset = $(cog).parent() if $asset.length < 1

    assetId = n.id($asset)

    # Trigger custom event on asset
    $asset.trigger 'edit', editing

    # Close all popovers if done editing
    $asset.find('[data-ui=popover]').popover('hide') unless editing

    # Handle all surf-to-edit fields
    $asset.find('[data-ui]').each -> n.field(this, $asset, editing)


  # Toolbar Toggle behaviour
  $(cog).on 'click', (event) ->
    $(cog).toggleClass('active')
    $(cog).trigger('edit', $(cog).hasClass('active'))
    $toolbar.toggle()
    $pageContent.parent().toggleClass('edit-mode') if $(cog).data('usingGrid')

  # Edit/Remove
  $toolbar.find('button.edit').on 'click', (event) -> location.href = $(this).data('href')
  # $toolbar.find('button.edit').on 'contextmenu', (event) -> window.open($(this).data('href'), '_blank', 'width=800,height=600,toolbar=yes,location=yes,directories=yes,status=yes,menubar=yes,scrollbars=yes,copyhistory=yes, resizable=yes'); return false

  # In development
  $toolbar.find('button.edit').on 'contextmenu', (event) -> 
    url = $(this).data('popover-href')
    $.get url, (response) -> 
      $toolbar.append('<div class=nform></div>')
      $form = $toolbar.find('.nform').html(response)
      $form.dialog(
        height: 'auto'
        width: 800 
        modal: true
        title: 'Editing Asset'
      )
    return false

  $toolbar.find('button.remove').on 'click', (event) -> location.href = $(this).data('href') if confirm('Are you sure?')


n.field = (field, asset, editing) ->
  $field = $(field)
  $asset = $(asset)
  fieldId = n.id($field)
  assetId = n.id($asset)

  # Inline 
  if $field.data('ui') is 'inline'

    # Text Areas
    if $field.data('type') is 'textarea'

      if editing
        $field.attr('contenteditable', 'true')
        $asset.data fieldId, window.CKEDITOR.inline(fieldId)
        $field.data 'original-value', n.cleanValue($field) 

      else
        $field.removeAttr('contenteditable')
        $asset.data(fieldId).destroy() if $asset.data(fieldId)
        n.gb.updateField($field) if $field.data('original-value') isnt n.cleanValue($field) 


    # Text Inputs
    if $field.data('type') is 'text'

        if editing
          $field.attr('contenteditable', 'true')
          $field.data 'original-value', n.cleanValue($field) 
        else
          $field.removeAttr('contenteditable')
          n.gb.updateField($field) if $field.data('original-value') isnt n.cleanValue($field) 


  # PopOver
  if $field.data('ui') is 'popover'

    # Editing
    if editing

      # After looping, show popup
      $field.data('popover', null).popover(
        placement: 'top'
        html: true
        container: 'body'
        title: n.cleanLabel($field)
        content: "<div id='#{fieldId}-popover' class='popover-form'>#{n.popoverHTML($field)}</div>" 


      # Build a form when the popover is opened
      ).on('shown.bs.popover', -> 
        $("##{fieldId}-popover").html(n.popoverHTML($field))


      # Update the values when the popover is closed
      ).on('hide.bs.popover', -> 

        # Each popover value
        $("##{fieldId}-popover .popover-value").each ->

          # Find the matching asset field
          $f = $("##{assetId} [data-field=#{$(this).prop('name')}]")

          # Update the asset field's value with the popover value
          $f.data 'value', $(this).prop('value')
      )



    # Updating
    else
      for $f in $field.data('fields')

        # Text Inputs
        if $f.data('type') is 'text'
          n.gb.updateField($field) if $f.data('original-value') isnt n.cleanValue($f) 


# 
n.popoverHTML = ($field) ->
  popoverHTML = ''

  unless $field.data('fields')
    $fields = []
    $fields.push $field if $field.data('type')
    $field.find('[data-type]').each -> $fields.push $(this)
    $field.data('fields', $fields)

  for $f in $field.data('fields')

    # Text Inputs
    if $f.data('type') is 'text'
      $f.data 'original-value', n.cleanValue($f) 
      popoverHTML += """
        <label>
          <span>#{n.cleanLabel($f, $field.data('fields').length)}</span>
          <input type='text' class='popover-value' name='#{$f.data('field')}' value='#{n.cleanValue($f)}'>
        </label>
      """

    # Other Inputs
    # if $f.data('type') is 'textarea'

  popoverHTML



# Behaviour for the container buttons
n.toolbarPage = (toolbar) ->
  $toolbar = $(toolbar)
  $toolbar.find('button.add').on 'click', (event) -> location.href = $(this).data('href')
  $toolbar.find('button.add').on 'contextmenu', (event) -> window.open($(this).data('href'), '_blank', 'width=800,height=600,toolbar=yes,location=yes,directories=yes,status=yes,menubar=yes,scrollbars=yes,copyhistory=yes, resizable=yes'); return false
  $toolbar.find('button.order').on 'click', (event) -> window.open $(this).data('href'), 'sort', 'width=500, height=550, resizable, scrollbars'


# GridBlock behaviour
n.gb = {}

#
n.gb.findInherited = ($a) ->
  grid = $a.data('grid')
  $gb = $a.closest('.grid-block')

  type = 'col' if $a.data('col')?
  type = 'row' if $a.data('row')?

  if grid is 'sm' or grid is 'md' or grid is 'lg'
    i = $gb.data("#{type}-xs") if $gb.data("#{type}-xs") isnt 'inherit'
  if grid is 'md' or grid is 'lg'
    i = $gb.data("#{type}-sm") if $gb.data("#{type}-sm") isnt 'inherit'
  if grid is 'lg'
    i = $gb.data("#{type}-md") if $gb.data("#{type}-md") isnt 'inherit'

  $a.closest(".#{type}-chooser").find("a[data-#{type}=#{i}]")


# 
n.gb.updateField = ($field, skipVersioning=false) ->
  noty layout: 'bottomRight', type: 'alert', timeout: 2000, text: 'Saving...'

  # Basic asset info
  $gb = $field.closest('.grid-block')
  asset = 
    id: $gb.data('asset-id')
    cms_headline: $gb.data('asset-headline')
    __submit__: true
    __skip_versioning__: skipVersioning

  # Inline editing can mess up page id links. Don't let surftoedit links get saved:
  fieldHTML = $field.html().replace(/\/nterchange\/page\/surftoedit\//g, '/_page')

  # Changes made
  if $field.data('type') is 'textarea'
    asset[$field.data('field')] = fieldHTML

  if $field.data('type') is 'text'
    asset[$field.data('field')] = fieldHTML 

  # Post the changes and notify
  $.post("/nterchange/#{$gb.data('asset')}/edit/#{$gb.data('asset-id')}", asset).done -> 
    noty layout: 'bottomRight', type: 'success', timeout: 1000, text: 'Changes Saved!'
    $field.trigger 'update'


#
n.gb.updatePageContent = ($gb) ->
  # noty layout: 'bottomRight', type: 'alert', timeout: 2000, text: 'Saving...'

  # Basic block info
  gb = 
    id: $gb.data('id')
    __submit__: true

  # Changes made
  gb['col_xs']        = $gb.data('col-xs')
  gb['col_sm']        = $gb.data('col-sm')
  gb['col_md']        = $gb.data('col-md')
  gb['col_lg']        = $gb.data('col-lg')
  gb['row_xs']        = $gb.data('row-xs')
  gb['row_sm']        = $gb.data('row-sm')
  gb['row_md']        = $gb.data('row-md')
  gb['row_lg']        = $gb.data('row-lg')
  gb['pull_xs']       = $gb.data('pull-xs')
  gb['pull_sm']       = $gb.data('pull-sm')
  gb['pull_md']       = $gb.data('pull-md')
  gb['pull_lg']       = $gb.data('pull-lg')
  gb['content_order'] = $gb.data('content-order')

  # Post the changes and notify
  $.post("/nterchange/page_content/edit/#{$gb.data('id')}", gb).done -> 
    noty layout: 'bottomRight', type: 'success', timeout: 1000, text: 'Changes Saved!'


# Classify what's active
n.gb.classify = (gb) ->
  $gb = $(gb)

  for g, i of n.grids
    for type in ['col', 'row']
      num = $gb.data("#{type}-#{g}")
      $a = $gb.find(".ntoolbar .#{type}-chooser-#{g} a[data-#{type}=#{num}]")
      $a.addClass('active')
      if num is 'inherit'
        $ia = n.gb.findInherited($a)
        $ia.addClass('inherit')
        num = "#{$ia.data(type)} <i>(inherit)</i>"
      $gb.find(".grid-block-dimensions-#{g} .grid-block-#{type}").html(num)


    # Pull left/right
    $left = $gb.find(".ntoolbar button.pull-left[data-grid=#{g}]")
    $right =  $gb.find(".ntoolbar button.pull-right[data-grid=#{g}]")

    if $gb.data("pull-#{g}") is 'right'
      $right.addClass('active')
      $left.addClass('inactive')
    else if $gb.data("pull-#{g}") is 'left'
      $right.addClass('inactive')
      $left.addClass('active')
    else
      $right.addClass('inactive')
      $left.addClass('inactive')


# jQuery UI resizable trick
n.gb.resizable = (gb) ->
  $gb = $(gb)

  # Resize
  col_width = ($gb.closest('.row').width() / 12)
  row_height = parseInt($gb.css('font-size'), 10)

  $gb.find('.grid-block-content').resizable(
    handles: 's, e'

    start: (event, ui) -> 
      $gb = ui.element.closest('.grid-block')
      resize = 'horizontal' if $(event.toElement).hasClass('ui-resizable-e')
      resize = 'vertical'   if $(event.toElement).hasClass('ui-resizable-s')
      $gb.data('resize', resize).addClass('resizing')

    # stop: (event, ui) -> 
    resize: (event, ui) -> 
      grid = $('html').data('grid')
      $gb = ui.element.closest('.grid-block')

      # Horizontal resize
      if $gb.data('resize') is 'horizontal'
        col = Math.round(ui.size.width / col_width)
        col = 0 if col < 1
        col = 12 if col > 12
        n.gb.grid $gb.find(".ntoolbar .col-chooser-#{grid} a[data-col=#{col}]"), false

      # Vertical resize
      if $gb.data('resize') is 'vertical'
        row = Math.round(ui.size.height / row_height)
        row = 0 if row < 1
        row = 50 if row > 50
        n.gb.grid $gb.find(".ntoolbar .row-chooser-#{grid} a[data-row=#{row}]"), false

      # Run it without saving
      n.gb.grid $gb.find($gb.data('grid-selector')), false


    stop: (event, ui) -> 
      $gb = ui.element.closest('.grid-block')
      $gb.removeClass('resizing')
      # Save the changes
      n.gb.updatePageContent($gb)
  )

  # Reset height to auto/inherit on double-click
  $gb.find('.ui-resizable-s').dblclick (event) ->
    grid = $('html').data('grid')
    $gb = $(event.target).closest('.grid-block')
    row = 'auto'
    row = 'inherit' if $gb.data("row-#{grid}") is 'auto'
    $gb.data("row-#{grid}", row)
    n.gb.grid $gb.find(".ntoolbar .row-chooser-#{grid} a[data-row=#{row}]"), true

  # Reset width to full/inherit on double-click
  $gb.find('.ui-resizable-e').dblclick (event) ->
    grid = $('html').data('grid')
    $gb = $(event.target).closest('.grid-block')
    col = '12'
    col = 'inherit' if parseInt($gb.data("col-#{grid}"), 10) == 12
    $gb.data("col-#{grid}", col)
    n.gb.grid $gb.find(".ntoolbar .col-chooser-#{grid} a[data-col=#{col}]"), true


# Row/Col chooser
# n.gb.grid = (event, update=false) ->
n.gb.grid = ($a, update=false) ->
  type = 'col' if $a.data('col')?
  type = 'row' if $a.data('row')?

  grid = $a.data('grid')
  val = $a.data(type)
  $gb = $a.closest('.grid-block')
  # console.log($gb.html())
  $ch = $a.closest(".#{type}-chooser")

  # Save the changes
  $gb.data("#{type}-#{grid}", val).attr("data-#{type}-#{grid}", val)
  $gb.find('.grid-block-content').attr('style','')
  if update then n.gb.updatePageContent($gb) 

  # Show the changes right now
  $ch.find('a.active').removeClass('active')
  $a.addClass('active')

  num = $a.data(type)
  num = $(n.gb.findInherited($a)).data(type) + ' <i>(inherit)</i>' if num is 'inherit'
  $gb.find(".grid-block-dimensions-#{grid} .grid-block-#{type}").html(num)

  # Rerun all inherited values
  $gb.find(".#{type}-chooser a.inherit").removeClass('inherit')
  $gb.find(".#{type}-chooser a.active[data-#{type}=inherit]").each ->
    n.gb.findInherited($(this)).addClass('inherit')


# Pull left/Right toolbar
n.gb.pull = (event) ->
  $clicked = $(event.target).closest('button')

  if $clicked.hasClass('pull-left')
    clicked = 'left'
    other = 'right'
  else
    clicked = 'right'
    other = 'left'

  $other = $(event.target).closest('.pull-chooser').find("button.pull-#{other}")
  $gb = $clicked.closest('.grid-block')
  grid = $clicked.data('grid')

  if $clicked.hasClass('active')
    $clicked.removeClass('active').addClass('inactive')
    $gb.data("pull-#{grid}", 'none').attr("data-pull-#{grid}", 'none')

  else
    $other.removeClass('active').addClass('inactive')
    $clicked.removeClass('inactive').addClass('active')
    $gb.data("pull-#{grid}", clicked).attr("data-pull-#{grid}", clicked)

  # Save the changes
  n.gb.updatePageContent($gb)

