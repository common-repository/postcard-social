var PostcardModal, prettyDate, stylePostText;

stylePostText = function(message) {
  var text;
  text = message.text.replace(/((https?|s?ftp|ssh)\:\/\/[^"\s\<\>]*[^.,;'">\:\s\<\>\)\]\!])/g, function(F) {
    return '<a target="_blank" href="' + F + '">' + F + "</a>";
  });
  text = text.replace(/\B@([_a-z0-9]+)/ig, function(F) {
    return F.charAt(0) + '<a target="_blank" href="http://www.twitter.com/' + F.substring(1) + '">' + F.substring(1) + "</a>";
  });
  return text = text.replace(/\B#([_a-z0-9]+)/ig, function(F) {
    return F.charAt(0) + '<a target="_blank" href="http://www.twitter.com/#!/search?q=' + F.substring(1) + '">' + F.substring(1) + "</a>";
  });
};

prettyDate = function(time) {
  var date, day_diff, diff;
  date = new Date(time.replace(/-/g, "/").replace("T", " ").replace("+", " +"));
  diff = ((new Date()).getTime() - date.getTime()) / 1000;
  day_diff = Math.floor(diff / 86400);
  if (isNaN(day_diff) || day_diff < 0) {
    return;
  }
  if (day_diff === 0) {
    if (diff < 60) {
      return Math.floor(diff) + " seconds ago";
    }
    if (diff < 120) {
      return "1 minute ago";
    }
    if (diff < 3600) {
      return Math.floor(diff / 60) + " minutes ago";
    }
    if (diff < 7200) {
      return "1 hour ago";
    }
    if (diff < 86400) {
      return Math.floor(diff / 3600) + " hours ago";
    }
  } else {
    if (day_diff === 1) {
      return "1 day ago";
    }
    if (day_diff < 7) {
      return day_diff + " days ago";
    }
    if (day_diff === 7) {
      return "1 week ago";
    }
    if (day_diff > 7) {
      return Math.ceil(day_diff / 7) + " weeks ago";
    }
  }
};

/*
  Class for handling the postard modal window
  Expects certain gallery/modal structure to be in place in the HTML
  i.e. exactly what the postcard-core HTML outputs when you output a gallery
*/


PostcardModal = (function() {
  PostcardModal.prototype.modalWindow = null;

  PostcardModal.prototype.currentPostcard = null;

  PostcardModal.prototype.mediaSectionDimensions = null;

  PostcardModal.prototype.params = null;

  function PostcardModal() {
    var _this = this;
    this.modalWindow = jQuery("#postcard-modal-window");
    this.modalWindow.click(function(e) {
      e.stopPropagation();
      jQuery(this).fadeOut();
      _this.modalWindow.find(".media-container").text("");
      return _this.modalWindow.find(".message-container .value").text("");
    });
    this.modalWindow.find(".postcard-modal").click(function(e) {
      return e.stopPropagation();
    });
    this.modalWindow.find(".next").click(function(e) {
      e.stopPropagation();
      return _this.next();
    });
    this.modalWindow.find(".prev").click(function(e) {
      e.stopPropagation();
      return _this.prev();
    });
  }

  PostcardModal.prototype.expand = function(id) {
    var _this = this;
    this.modalWindow.fadeIn();
    return jQuery.ajax("/?postcard_api=true&endpoint=post/get&id=" + id, {
      success: function(data, textStatus, jqXHR) {
        return _this.display(data.payload);
      },
      error: function(jqXHR, textStatus, errorThrown) {
        return console.error('bunk error', jqXHR, textStatus, errorThrown);
      }
    });
  };

  PostcardModal.prototype.next = function() {
    var url,
      _this = this;
    url = ("/?postcard_api=true&endpoint=post/search&before=" + this.currentPostcard.postcard_id + "&image=true&limit=1") + (this.params != null ? "&" + this.params : "");
    return jQuery.ajax(url, {
      success: function(data, textStatus, jqXHR) {
        if (data.payload.length >= 1) {
          return _this.display(data.payload[0]);
        }
      },
      error: function(jqXHR, textStatus, errorThrown) {
        return console.error('bunk error', jqXHR, textStatus, errorThrown);
      }
    });
  };

  PostcardModal.prototype.prev = function() {
    var url,
      _this = this;
    url = ("/?postcard_api=true&endpoint=post/search&since=" + this.currentPostcard.postcard_id + "&image=true&limit=1") + (this.params != null ? "&" + this.params : "");
    return jQuery.ajax(url, {
      success: function(data, textStatus, jqXHR) {
        if (data.payload.length >= 1) {
          return _this.display(data.payload[0]);
        }
      },
      error: function(jqXHR, textStatus, errorThrown) {
        return console.error('bunk error', jqXHR, textStatus, errorThrown);
      }
    });
  };

  PostcardModal.prototype.display = function(postcard) {
    var date, infoContainer, nHeight, nImg, nWidth, pLeft, pTop, paddingCSS, paddingCss, profile, vH, vW, video, video_id,
      _this = this;
    this.currentPostcard = postcard;
    this.modalWindow.find(".message-container .value").html(postcard.message);
    infoContainer = this.modalWindow.find(".info-container");
    profile = "<img class=\"profile-pic\" src=\"/?postcard_api=true&endpoint=user/picture&id=" + postcard.user_id + "\" />";
    infoContainer.html(profile);
    date = "<br /><span class=\"date\">" + (prettyDate(postcard.date)) + "</span>";
    infoContainer.append(date);
    this.calculateMediaSectionDimensions();
    if (postcard.video != null) {
      vW = postcard.width;
      vH = postcard.height;
      if (vW > vH) {
        nHeight = this.mediaSectionDimensions.height * (vH / vW);
        nWidth = this.mediaSectionDimensions.width;
        pTop = (this.mediaSectionDimensions.height - nHeight) / 2;
        paddingCss = {
          "padding-top": pTop + "px"
        };
      } else if (vH > vW) {
        nWidth = this.mediaSectionDimensions.width * (vW / vH);
        nHeight = this.mediaSectionDimensions.height;
        pLeft = (this.mediaSectionDimensions.width - nWidth) / 2;
        paddingCss = {
          "padding-left": pLeft + "px"
        };
      } else {
        nWidth = this.mediaSectionDimensions.width;
        nHeight = this.mediaSectionDimensions.height;
        paddingCSS = null;
      }
      video_id = ("postcard-video-" + postcard.postcard_id + "-") + (Math.floor(Math.random() * 11));
      video = jQuery("<video id=\"" + video_id + "\" class=\"video-js vjs-default-skin\" width=\"" + nWidth + "\" height=\"" + nHeight + "\" controls loop><source src=\"" + postcard.video + "\" type=\"video/mp4\"></video>");
      this.modalWindow.find(".media-container").html(video);
      video.attr('autoplay', 'autoplay');
      return videojs(video_id, {
        width: this.mediaSectionDimensions.width,
        height: this.mediaSectionDimensions.height
      }, function() {
        return console.log("video loaded");
      });
    } else {
      nImg = new Image();
      nImg.onload = function() {
        var iH, iW, img;
        img = jQuery("<img class=\"media\" src=\"" + postcard.image + "\" />");
        iW = this.width;;
        iH = this.height;;
        if (iW > iH) {
          nHeight = _this.mediaSectionDimensions.height * (iH / iW);
          pTop = (_this.mediaSectionDimensions.height - nHeight) / 2;
          img.css({
            "padding-top": pTop + "px"
          });
        } else {
          nWidth = _this.mediaSectionDimensions.width * (iW / iH);
          pLeft = (_this.mediaSectionDimensions.width - nWidth) / 2;
          img.css({
            "padding-left": pLeft + "px"
          });
        }
        img.fadeTo(0, 0);
        _this.modalWindow.find(".media-container").html(img);
        return img.fadeTo(400, 1);
      };
      return nImg.src = postcard.image;
    }
  };

  PostcardModal.prototype.calculateMediaSectionDimensions = function() {
    var mediaSection;
    mediaSection = this.modalWindow.find(".media-section");
    return this.mediaSectionDimensions = {
      width: mediaSection.width(),
      height: mediaSection.height()
    };
  };

  return PostcardModal;

})();

jQuery(document).ready(function() {
  var gallery, params, postcard;
  if (window.innerWidth <= 480 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
    jQuery('video.video-js').each(function() {
      var image_container, media_container, ratio, video, video_container_width;
      video = jQuery(this);
      video_container_width = video.parent().width();
      media_container = video.parents('.media-container');
      image_container = video.parent('.video-container').next();
      ratio = video_container_width / video.data('width');
      media_container.css({
        width: video_container_width + "px",
        height: (video.data('height') * ratio) + "px"
      });
      video.attr("width", video_container_width).attr("height", video.data('height') * ratio).attr("poster", video.data('poster'));
      if (image_container.hasClass('image-container')) {
        image_container.prepend('<span class="video-indicator"></span>');
      }
      return image_container.click(function() {
        image_container.hide();
        return video.get(0).play();
      });
    });
  } else {
    jQuery('video.video-js').each(function() {
      var image_container, options, video;
      video = jQuery(this);
      image_container = video.parent().next();
      console.log('image container', image_container);
      image_container.hide();
      video.parent().next().hide();
      options = {
        width: video.data('width') || video.attr('width'),
        height: video.data('height') || video.attr('height'),
        poster: video.data('poster') || video.attr('poster'),
        preload: "auto"
      };
      console.log('video options', options);
      return videojs(video.attr('id'), options);
    });
  }
  gallery = jQuery('.postcard-gallery');
  if (gallery.length > 0) {
    params = gallery.data("params");
    postcard = new PostcardModal();
    postcard.params = params;
    return jQuery(".postcard-gallery .postcard-container").click(function() {
      var pc_id;
      pc_id = jQuery(this).data("postcard-id");
      return postcard.expand(pc_id);
    });
  }
});
