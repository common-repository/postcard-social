stylePostText = (message) ->
  text = message.text.replace /((https?|s?ftp|ssh)\:\/\/[^"\s\<\>]*[^.,;'">\:\s\<\>\)\]\!])/g, (F) ->
    return '<a target="_blank" href="' + F + '">' + F + "</a>"
  text = text.replace /\B@([_a-z0-9]+)/ig, (F) ->
    return F.charAt(0) + '<a target="_blank" href="http://www.twitter.com/' + F.substring(1) + '">' + F.substring(1) + "</a>"
  text = text.replace /\B#([_a-z0-9]+)/ig, (F) ->
    return F.charAt(0) + '<a target="_blank" href="http://www.twitter.com/#!/search?q=' + F.substring(1) + '">' + F.substring(1) + "</a>"

prettyDate = (time) ->
  date = new Date(time.replace(/-/g, "/").replace("T", " ").replace("+", " +"))
  diff = (((new Date()).getTime() - date.getTime()) / 1000)
  day_diff = Math.floor(diff / 86400)

  if isNaN(day_diff) or day_diff < 0 then return

  if day_diff is 0
    if(diff < 60) then return Math.floor(diff) + " seconds ago"
    if(diff < 120) then return "1 minute ago"
    if(diff < 3600) then return Math.floor(diff / 60) + " minutes ago"
    if(diff < 7200) then return "1 hour ago"
    if(diff < 86400) then return Math.floor(diff / 3600) + " hours ago"
  else
    if(day_diff == 1) then return "1 day ago"
    if(day_diff < 7) then return day_diff + " days ago"
    if(day_diff == 7) then return "1 week ago"
    if(day_diff > 7) then return Math.ceil(day_diff / 7) + " weeks ago"

###
  Class for handling the postard modal window
  Expects certain gallery/modal structure to be in place in the HTML
  i.e. exactly what the postcard-core HTML outputs when you output a gallery
###
class PostcardModal
  modalWindow: null
  currentPostcard: null
  mediaSectionDimensions: null
  params: null #additional query parameters to include in next/prev search i.e. tags

  constructor: ->
    @modalWindow = jQuery("#postcard-modal-window")

    #on modal window click, dismiss it and remove content
    @modalWindow.click (e) =>
      e.stopPropagation()
      `jQuery(this).fadeOut()`
      @modalWindow.find(".media-container").text ""
      @modalWindow.find(".message-container .value").text ""

    #clicking inside the postcard itself shouldn't dismiss
    @modalWindow.find(".postcard-modal").click (e) ->
      e.stopPropagation()

    #clicking next/previous buttons should move to next/prev postcard
    @modalWindow.find(".next").click (e) =>
      e.stopPropagation()
      @next()
    @modalWindow.find(".prev").click (e) =>
      e.stopPropagation()
      @prev()

  #command to open modal and get data on a postcard to be displayed
  expand: (id) ->
    @modalWindow.fadeIn()
    jQuery.ajax "/?postcard_api=true&endpoint=post/get&id=#{id}",
      success: (data, textStatus, jqXHR) =>
        @display data.payload
      error: (jqXHR, textStatus, errorThrown) =>
        console.error 'bunk error', jqXHR, textStatus, errorThrown

  #looks at currently displayed postcard and gets next one to be displayed
  next: ->
    url = "/?postcard_api=true&endpoint=post/search&before=#{@currentPostcard.postcard_id}&image=true&limit=1" + if @params? then "&#{@params}" else ""
    jQuery.ajax url,
      success: (data, textStatus, jqXHR) =>
        if data.payload.length >= 1
          @display data.payload[0]
      error: (jqXHR, textStatus, errorThrown) =>
        console.error 'bunk error', jqXHR, textStatus, errorThrown

  #looks at currently displayed postcard and gets previous one to be displayed
  prev: ->
    url = "/?postcard_api=true&endpoint=post/search&since=#{@currentPostcard.postcard_id}&image=true&limit=1" + if @params? then "&#{@params}" else ""
    jQuery.ajax url,
      success: (data, textStatus, jqXHR) =>
        if data.payload.length >= 1
          @display data.payload[0]
      error: (jqXHR, textStatus, errorThrown) =>
        console.error 'bunk error', jqXHR, textStatus, errorThrown

  #takes a postcard object and sets it up for display
  display: (postcard) ->
    @currentPostcard = postcard
    @modalWindow.find(".message-container .value").html postcard.message

    infoContainer = @modalWindow.find(".info-container")
    profile = "<img class=\"profile-pic\" src=\"/?postcard_api=true&endpoint=user/picture&id=#{postcard.user_id}\" />"
    infoContainer.html profile

    date = "<br /><span class=\"date\">#{prettyDate(postcard.date)}</span>"
    infoContainer.append date

    @calculateMediaSectionDimensions()

    if postcard.video?
      vW = postcard.width
      vH = postcard.height
      if vW > vH
        nHeight = @mediaSectionDimensions.height * (vH / vW)
        nWidth = @mediaSectionDimensions.width
        pTop = (@mediaSectionDimensions.height - nHeight) / 2
        paddingCss =
          "padding-top": pTop + "px"
      else if vH > vW
        nWidth = @mediaSectionDimensions.width * (vW / vH)
        nHeight = @mediaSectionDimensions.height
        pLeft = (@mediaSectionDimensions.width - nWidth) / 2
        paddingCss =
          "padding-left": pLeft + "px"
      else
        nWidth = @mediaSectionDimensions.width
        nHeight = @mediaSectionDimensions.height
        paddingCSS = null


      video_id = "postcard-video-#{postcard.postcard_id}-" + (Math.floor(Math.random() * 11))
      video = jQuery("<video id=\"#{video_id}\" class=\"video-js vjs-default-skin\" width=\"#{nWidth}\" height=\"#{nHeight}\" controls loop><source src=\"#{postcard.video}\" type=\"video/mp4\"></video>")
      @modalWindow.find(".media-container").html video
      video.attr 'autoplay', 'autoplay'
      #video.css paddingCss
      videojs(video_id, {width: @mediaSectionDimensions.width, height: @mediaSectionDimensions.height}, ->
        console.log "video loaded"
      )

    else
      nImg = new Image()
      nImg.onload = =>
        img = jQuery("<img class=\"media\" src=\"#{postcard.image}\" />")
        iW = `this.width;`
        iH = `this.height;`
        if (iW > iH)
          nHeight = @mediaSectionDimensions.height * (iH / iW)
          pTop = (@mediaSectionDimensions.height - nHeight) / 2
          img.css
            "padding-top": pTop + "px"
        else
          nWidth = @mediaSectionDimensions.width * (iW / iH)
          pLeft = (@mediaSectionDimensions.width - nWidth) / 2
          img.css
            "padding-left": pLeft + "px"
        img.fadeTo 0, 0
        @modalWindow.find(".media-container").html img
        img.fadeTo 400, 1
      nImg.src = postcard.image

  calculateMediaSectionDimensions: ->
    mediaSection = @modalWindow.find(".media-section")
    @mediaSectionDimensions =
      width: mediaSection.width()
      height: mediaSection.height()

jQuery(document).ready () ->
  #video code for feed views
  if window.innerWidth <= 480 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
    jQuery('video.video-js').each ->
      video = jQuery(@)
      video_container_width = video.parent().width()
      media_container = video.parents('.media-container')
      image_container = video.parent('.video-container').next()
      ratio = video_container_width / video.data('width')
      media_container.css({
        width: video_container_width + "px"
        height: (video.data('height') * ratio) + "px"
      })

      video.attr("width", video_container_width)
      .attr("height", video.data('height') * ratio)
      .attr("poster", video.data('poster'))

      if image_container.hasClass 'image-container'
        image_container.prepend('<span class="video-indicator"></span>')

      image_container.click ->
        image_container.hide()
        video.get(0).play()
  else
    jQuery('video.video-js').each ->
      video = jQuery(@)
      image_container = video.parent().next()
      console.log 'image container', image_container
      image_container.hide()

      video.parent().next().hide() #hide the image
      options = {
        width: video.data('width') || video.attr('width')
        height: video.data('height') || video.attr('height')
        poster: video.data('poster') || video.attr('poster')
        preload: "auto"
      }
      console.log 'video options', options
      videojs video.attr('id'), options

  #gallery view code
  #check if there's one on the page and prepare it
  gallery = jQuery('.postcard-gallery')
  if gallery.length > 0
    params = gallery.data "params"
    postcard = new PostcardModal()
    postcard.params = params
    jQuery(".postcard-gallery .postcard-container").click ->
      pc_id = jQuery(@).data "postcard-id"
      postcard.expand pc_id