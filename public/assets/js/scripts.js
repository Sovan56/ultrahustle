"use strict";

$(window).on("load", function () {
  $(".loader").fadeOut("slow");
});

feather.replace();

$(function () {
  // ------------------- Nicescroll / layout setup -------------------
  let sidebar_nicescroll_opts = {
      cursoropacitymin: 0,
      cursoropacitymax: 0.8,
      zindex: 892
    },
    now_layout_class = null;

  var sidebar_sticky = function () {
    if ($("body").hasClass("layout-2")) {
      $("body.layout-2 #sidebar-wrapper").stick_in_parent({ parent: $("body") });
      $("body.layout-2 #sidebar-wrapper").stick_in_parent({ recalc_every: 1 });
    }
  };
  sidebar_sticky();

  var sidebar_nicescroll;
  var update_sidebar_nicescroll = function () {
    let a = setInterval(function () {
      if (sidebar_nicescroll != null) sidebar_nicescroll.resize();
    }, 10);
    setTimeout(function () { clearInterval(a); }, 600);
  };

  var sidebar_dropdown = function () {
    if ($(".main-sidebar").length) {
      $(".main-sidebar").niceScroll(sidebar_nicescroll_opts);
      sidebar_nicescroll = $(".main-sidebar").getNiceScroll();

      $(".main-sidebar .sidebar-menu li a.has-dropdown")
        .off("click")
        .on("click", function () {
          $(this).parent().find("> .dropdown-menu").slideToggle(500, function () {
            update_sidebar_nicescroll();
            return false;
          });
          return false;
        });
    }
  };
  sidebar_dropdown();

  if ($("#top-5-scroll").length) {
    $("#top-5-scroll").css({ height: 315 }).niceScroll();
  }
  if ($("#scroll-new").length) {
    $("#scroll-new").css({ height: 200 }).niceScroll();
  }

  $(".main-content").css({ minHeight: $(window).outerHeight() - 95 });

  $(".nav-collapse-toggle").click(function () {
    $(this).parent().find(".navbar-nav").toggleClass("show");
    return false;
  });
  $(document).on("click", function () { $(".nav-collapse .navbar-nav").removeClass("show"); });

  // ------------------- Togglers used by theme persistence -------------------
  var toggle_sidebar_mini = function (mini) {
    let body = $("body");
    if (!mini) {
      body.removeClass("sidebar-mini");
      $(".main-sidebar").css({ overflow: "hidden" });
      setTimeout(function () {
        $(".main-sidebar").niceScroll(sidebar_nicescroll_opts);
        sidebar_nicescroll = $(".main-sidebar").getNiceScroll();
      }, 500);
      $(".main-sidebar .sidebar-menu > li > ul .dropdown-title").remove();
      $(".main-sidebar .sidebar-menu > li > a").removeAttr("data-toggle data-original-title title");
    } else {
      body.addClass("sidebar-mini");
      body.removeClass("sidebar-show");
      if (sidebar_nicescroll) {
        sidebar_nicescroll.remove();
        sidebar_nicescroll = null;
      }
      $(".main-sidebar .sidebar-menu > li").each(function () {
        let me = $(this);
        if (me.find("> .dropdown-menu").length) {
          me.find("> .dropdown-menu").hide();
          me.find("> .dropdown-menu").prepend('<li class="dropdown-title pt-3">' + me.find("> a").text() + "</li>");
        } else {
          me.find("> a").attr("data-toggle", "tooltip");
          me.find("> a").attr("data-original-title", me.find("> a").text());
          $("[data-toggle='tooltip']").tooltip({ placement: "right" });
        }
      });
    }
  };

  var toggle_sticky_header = function (sticky) {
    if (!sticky) {
      $(".main-navbar")[0] && $(".main-navbar")[0].classList.remove("sticky");
    } else {
      $(".main-navbar")[0] && ($(".main-navbar")[0].classList += " sticky");
    }
  };

  // ------------------- THEME PERSISTENCE (localStorage) -------------------
  const THEME_KEY = "adminThemeV1";
  // include 'pedigree' token for chip syncing (even though it toggles brand-inverse)
  const THEME_COLORS = ["white", "black", "cyan", "purple", "orange", "green", "red", "pedigree"];

  // Default theme when nothing is saved yet
  const DEFAULT_THEME = {
    layout: "fullblack",          // Full Black by default (bg #000000, text #CEFF1B)
    sidebar: "dark-sidebar",
    color: "white",
    mini: false,
    sticky: true,
    brandInverse: false           // true only when Pedigree (lime bg, black text/icons) is active
  };

  function saveThemeState(state) {
    try { localStorage.setItem(THEME_KEY, JSON.stringify(state)); } catch (e) {}
  }
  function loadThemeState() {
    try {
      const parsed = JSON.parse(localStorage.getItem(THEME_KEY) || "null");
      return parsed || DEFAULT_THEME;
    } catch (e) { return DEFAULT_THEME; }
  }

  // Pick up state from DOM (so persistNow works after manual class changes)
  function deriveThemeFromDOM() {
    const body = $("body");
    const layout = body.hasClass("fullblack") ? "fullblack" : (body.hasClass("dark") ? "dark" : "light");
    const sidebar = body.hasClass("dark-sidebar") ? "dark-sidebar" : "light-sidebar";
    const m = (body.attr("class") || "").match(/theme-(white|black|cyan|purple|orange|green|red|pedigree)/i);
    const color = m ? m[1].toLowerCase() : "white";
    const mini = body.hasClass("sidebar-mini");
    const sticky = $(".main-navbar").length ? $(".main-navbar").hasClass("sticky") : false;
    const brandInverse = body.hasClass("brand-inverse"); // Pedigree inverse flag
    return { layout, sidebar, color, mini, sticky, brandInverse };
  }

  // Apply state to DOM and sync all UI controls
  function applyThemeState(state) {
    const body = $("body");
    const all = [
      "light","dark","fullblack",                   // layouts
      "light-sidebar","dark-sidebar",               // sidebar
      "brand-inverse",                              // inverse (Pedigree)
      ...THEME_COLORS.map(c=>"theme-"+c)            // color tokens
    ].join(" ");
    body.removeClass(all);

    // apply layout + sidebar + theme token
    body.addClass(state.layout);
    body.addClass(state.sidebar);
    body.addClass("theme-" + state.color);

    // Pedigree inverse: lime background with black text/icons
    if (state.brandInverse) body.addClass("brand-inverse");

    // sync UI controls
    const layoutRadioVal = state.layout === "light" ? "1" : state.layout === "dark" ? "2" : "3";
    $(`.layout-color .selectgroup-input-radio[value="${layoutRadioVal}"]`).prop("checked", true);
    $(`.sidebar-color .selectgroup-input[value="${state.sidebar === "light-sidebar" ? "1" : "2"}"]`).prop("checked", true);
    $(".choose-theme li").removeClass("active");
    $(`.choose-theme li[title="${state.color === "pedigree" ? "Pedigree" : state.color}"]`).addClass("active");
    $("#mini_sidebar_setting").prop("checked", !!state.mini);
    $("#sticky_header_setting").prop("checked", !!state.sticky);

    // apply mini & sticky
    toggle_sidebar_mini(!!state.mini);
    toggle_sticky_header(!!state.sticky);
  }

  function persistNow() { saveThemeState(deriveThemeFromDOM()); }

  function applySavedOrDefault() {
    const saved = loadThemeState();
    applyThemeState(saved);
  }
  // ------------------------------------------------------------------------

  $(".menu-toggle").on("click", function () { $(this).toggleClass("toggled"); });

  $.each($(".main-sidebar .sidebar-menu li.active"), function (i, val) {
    var $activeAnchors = $(val).find("a:eq(0)");
    $activeAnchors.addClass("toggled");
    $activeAnchors.next().show();
  });

  $("[data-toggle='sidebar']").click(function () {
    var body = $("body"), w = $(window);
    if (w.outerWidth() <= 1024) {
      body.removeClass("search-show search-gone");
      if (body.hasClass("sidebar-gone")) { body.removeClass("sidebar-gone").addClass("sidebar-show"); }
      else { body.addClass("sidebar-gone").removeClass("sidebar-show"); }
      update_sidebar_nicescroll();
    } else {
      body.removeClass("search-show search-gone");
      if (body.hasClass("sidebar-mini")) { toggle_sidebar_mini(false); }
      else { toggle_sidebar_mini(true); }
    }
    return false;
  });

  var toggleLayout = function () {
    var w = $(window),
      layout_class = $("body").attr("class") || "",
      layout_classes = layout_class.trim().length > 0 ? layout_class.split(" ") : [];

    if (layout_classes.length > 0) {
      layout_classes.forEach(function (item) {
        if (item.indexOf("layout-") != -1) { now_layout_class = item; }
      });
    }

    if (w.outerWidth() <= 1024) {
      if ($("body").hasClass("sidebar-mini")) {
        toggle_sidebar_mini(false);
        $(".main-sidebar").niceScroll(sidebar_nicescroll_opts);
        sidebar_nicescroll = $(".main-sidebar").getNiceScroll();
      }

      $("body").addClass("sidebar-gone");
      $("body").removeClass("layout-2 layout-3 sidebar-mini sidebar-show");
      $("body").off("click").on("click", function (e) {
        if ($(e.target).hasClass("sidebar-show") || $(e.target).hasClass("search-show")) {
          $("body").removeClass("sidebar-show").addClass("sidebar-gone").removeClass("search-show");
          update_sidebar_nicescroll();
        }
      });

      update_sidebar_nicescroll();

      if (now_layout_class == "layout-3") {
        let nav_second_classes = $(".navbar-secondary").attr("class"),
          nav_second = $(".navbar-secondary");

        nav_second.attr("data-nav-classes", nav_second_classes);
        nav_second.removeAttr("class");
        nav_second.addClass("main-sidebar");

        let main_sidebar = $(".main-sidebar");
        main_sidebar.find(".container").addClass("sidebar-wrapper").removeClass("container");
        main_sidebar.find(".navbar-nav").addClass("sidebar-menu").removeClass("navbar-nav");
        main_sidebar.find(".sidebar-menu .nav-item.dropdown.show a").click();
        main_sidebar.find(".sidebar-brand").remove();
        main_sidebar.find(".sidebar-menu").before(
          $("<div>", { class: "sidebar-brand" }).append(
            $("<a>", { href: $(".navbar-brand").attr("href") }).html($(".navbar-brand").html())
          )
        );
        setTimeout(function () {
          sidebar_nicescroll = main_sidebar.niceScroll(sidebar_nicescroll_opts);
          sidebar_nicescroll = main_sidebar.getNiceScroll();
        }, 700);

        sidebar_dropdown();
        $(".main-wrapper").removeClass("container");
      }
    } else {
      $("body").removeClass("sidebar-gone sidebar-show");
      if (now_layout_class) $("body").addClass(now_layout_class);

      let nav_second_classes = $(".main-sidebar").attr("data-nav-classes"),
        nav_second = $(".main-sidebar");

      if (now_layout_class == "layout-3" && nav_second.hasClass("main-sidebar")) {
        nav_second.find(".sidebar-menu li a.has-dropdown").off("click");
        nav_second.find(".sidebar-brand").remove();
        nav_second.removeAttr("class");
        nav_second.addClass(nav_second_classes);

        let main_sidebar = $(".navbar-secondary");
        main_sidebar.find(".sidebar-wrapper").addClass("container").removeClass("sidebar-wrapper");
        main_sidebar.find(".sidebar-menu").addClass("navbar-nav").removeClass("sidebar-menu");
        main_sidebar.find(".dropdown-menu").hide();
        main_sidebar.removeAttr("style tabindex data-nav-classes");
        $(".main-wrapper").addClass("container");
      } else if (now_layout_class == "layout-2") {
        $("body").addClass("layout-2");
      } else {
        update_sidebar_nicescroll();
      }
    }
  };
  toggleLayout();
  $(window).resize(toggleLayout);

  $("[data-toggle='search']").click(function () {
    var body = $("body");
    if (body.hasClass("search-gone")) { body.addClass("search-gone").removeClass("search-show"); }
    else { body.removeClass("search-gone").addClass("search-show"); }
  });

  // tooltip / popover
  $("[data-toggle='tooltip']").tooltip();
  $('[data-toggle="popover"]').popover({ container: "body" });

  // Select2
  if (jQuery().select2) { $(".select2").select2(); }

  // Selectric
  if (jQuery().selectric) {
    $(".selectric").selectric({ disableOnMobile: false, nativeOnMobile: false });
  }

  $(".notification-toggle").dropdown();
  $(".notification-toggle").parent().on("shown.bs.dropdown", function () {
    $(".dropdown-list-icons").niceScroll({ cursoropacitymin: 0.3, cursoropacitymax: 0.8, cursorwidth: 7 });
  });

  $(".message-toggle").dropdown();
  $(".message-toggle").parent().on("shown.bs.dropdown", function () {
    $(".dropdown-list-message").niceScroll({ cursoropacitymin: 0.3, cursoropacitymax: 0.8, cursorwidth: 7 });
  });

  if (jQuery().summernote) {
    $(".summernote").summernote({ dialogsInBody: true, minHeight: 250 });
    $(".summernote-simple").summernote({
      dialogsInBody: true,
      minHeight: 150,
      toolbar: [["style", ["bold", "italic", "underline", "clear"]], ["font", ["strikethrough"]], ["para", ["paragraph"]]]
    });
  }

  // Dismiss function
  $("[data-dismiss]").each(function () {
    var me = $(this), target = me.data("dismiss");
    me.click(function () { $(target).fadeOut(function () { $(target).remove(); }); return false; });
  });

  // Collapsable
  $("[data-collapse]").each(function () {
    var me = $(this), target = me.data("collapse");
    me.click(function () {
      $(target).collapse("toggle");
      $(target).on("shown.bs.collapse", function () { me.html('<i class="fas fa-minus"></i>'); });
      $(target).on("hidden.bs.collapse", function () { me.html('<i class="fas fa-plus"></i>'); });
      return false;
    });
  });

  // Background
  $("[data-background]").each(function () {
    $(this).css({ backgroundImage: "url(" + $(this).data("background") + ")" });
  });

  // Custom Tab
  $("[data-tab]").each(function () {
    var me = $(this);
    me.click(function () {
      if (!me.hasClass("active")) {
        var tab_group = $('[data-tab-group="' + me.data("tab") + '"]'),
          tab_group_active = $('[data-tab-group="' + me.data("tab") + '"].active'),
          target = $(me.attr("href")),
//
          links = $('[data-tab="' + me.data("tab") + '"]');
        links.removeClass("active"); me.addClass("active"); target.addClass("active"); tab_group_active.removeClass("active");
      }
      return false;
    });
  });

  // Bootstrap 4 Validation
  $(".needs-validation").submit(function () {
    var form = $(this);
    if (form[0].checkValidity() === false) {
      event.preventDefault(); event.stopPropagation();
    }
    form.addClass("was-validated");
  });

  // alert dismissible
  $(".alert-dismissible").each(function () {
    var me = $(this);
    me.find(".close").click(function () { me.alert("close"); });
  });

  // Image cropper
  $("[data-crop-image]").each(function () {
    $(this).css({ overflow: "hidden", position: "relative", height: $(this).data("crop-image") });
  });

  // Slide Toggle
  $("[data-toggle-slide]").click(function () {
    $($(this).data("toggle-slide")).slideToggle();
    return false;
  });

  // Dismiss modal
  $("[data-dismiss=modal]").click(function () {
    $(this).closest(".modal").modal("hide");
    return false;
  });

  // Width/Height attributes
  $("[data-width]").each(function () { $(this).css({ width: $(this).data("width") }); });
  $("[data-height]").each(function () { $(this).css({ height: $(this).data("height") }); });

  // Chocolat
  if ($(".chocolat-parent").length && jQuery().Chocolat) { $(".chocolat-parent").Chocolat(); }

  // Sortable card
  if ($(".sortable-card").length && jQuery().sortable) {
    $(".sortable-card").sortable({ handle: ".card-header", opacity: 0.8, tolerance: "pointer" });
  }

  // Daterangepicker
  if (jQuery().daterangepicker) {
    if ($(".datepicker").length) {
      $(".datepicker").daterangepicker({ locale: { format: "YYYY-MM-DD" }, singleDatePicker: true });
    }
    if ($(".datetimepicker").length) {
      $(".datetimepicker").daterangepicker({
        locale: { format: "YYYY-MM-DD hh:mm" }, singleDatePicker: true, timePicker: true, timePicker24Hour: true
      });
    }
    if ($(".daterange").length) {
      $(".daterange").daterangepicker({ locale: { format: "YYYY-MM-DD" }, drops: "down", opens: "right" });
    }
  }

  // Timepicker
  if (jQuery().timepicker && $(".timepicker").length) {
    $(".timepicker").timepicker({ icons: { up: "fas fa-chevron-up", down: "fas fa-chevron-down" } });
  }

  // ------------ Settings panel interactions ------------
  $("#mini_sidebar_setting").on("change", function () {
    var checked = $(this).is(":checked");
    if (checked) toggle_sidebar_mini(true); else toggle_sidebar_mini(false);
    persistNow();
  });

  $("#sticky_header_setting").on("change", function () {
    if ($(".main-navbar")[0].classList.contains("sticky")) { toggle_sticky_header(false); }
    else { toggle_sticky_header(true); }
    persistNow();
  });

  $(".theme-setting-toggle").on("click", function () {
    if ($(".theme-setting")[0].classList.contains("active")) {
      $(".theme-setting")[0].classList.remove("active");
    } else {
      $(".theme-setting")[0].classList += " active";
    }
  });

  $(document).on("click", ".fullscreen-btn", function () {
    if (!document.fullscreenElement &&
        !document.mozFullScreenElement &&
        !document.webkitFullscreenElement &&
        !document.msFullscreenElement) {
      if (document.documentElement.requestFullscreen) document.documentElement.requestFullscreen();
      else if (document.documentElement.msRequestFullscreen) document.documentElement.msRequestFullscreen();
      else if (document.documentElement.mozRequestFullScreen) document.documentElement.mozRequestFullScreen();
      else if (document.documentElement.webkitRequestFullscreen) document.documentElement.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
    } else {
      if (document.exitFullscreen) document.exitFullscreen();
      else if (document.msExitFullscreen) document.msExitFullscreen();
      else if (document.mozCancelFullScreen) document.mozCancelFullScreen();
      else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
    }
  });

  $(".settingPanelToggle").on("click", function () {
    $(".settingSidebar").toggleClass("showSettingPanel");
  });
  $(".page-wrapper").on("click", function () {
    $(".settingSidebar").removeClass("showSettingPanel");
  });
  var mouse_is_inside = false;
  $(".settingSidebar").hover(function () { mouse_is_inside = true; }, function () { mouse_is_inside = false; });
  $("body").mouseup(function () {
    if (!mouse_is_inside) $(".settingSidebar").removeClass("showSettingPanel");
  });
  $(".settingSidebar-body").niceScroll();

  // theme color chips (includes Pedigree handling)
  $(".choose-theme li").on("click", function () {
    var bodytag = $("body"),
        prevTheme = $(".choose-theme li.active").attr("title"),
        nextTheme = ($(this).attr("title") || "").toLowerCase();

    $(".choose-theme li").removeClass("active");
    $(this).addClass("active");

    // remove previous theme-* token
    if (prevTheme) bodytag.removeClass("theme-" + prevTheme);

    // apply new theme token
    bodytag.addClass("theme-" + (nextTheme || "white"));

    // Pedigree → lime background (#CEFF1B) + black text/icons
    if (nextTheme === "pedigree") {
      bodytag.addClass("brand-inverse");
    } else {
      bodytag.removeClass("brand-inverse");
    }

    persistNow();
  });

  // sidebar color radios
  $(".sidebar-color input:radio").change(function () {
    if ($(this).val() == "1") {
      $("body").removeClass("dark-sidebar").addClass("light-sidebar");
    } else {
      $("body").removeClass("light-sidebar").addClass("dark-sidebar");
    }
    persistNow();
  });

  // layout color radios (1=Light, 2=Dark, 3=Full Black)
  $(".layout-color input:radio").change(function () {
    const v = String($(this).val());
    const body = $("body");

    if (v === "1") {
      body.removeClass().addClass("light light-sidebar theme-white");
      body.removeClass("brand-inverse"); // ensure inverse off in light
      $(".choose-theme li").removeClass("active");
      $(".choose-theme li[title='white']").addClass("active");
      $(".selectgroup-input[value='1']").prop("checked", true);
    } else if (v === "2") {
      body.removeClass().addClass("dark dark-sidebar theme-black");
      body.removeClass("brand-inverse"); // ensure inverse off in dark
      $(".choose-theme li").removeClass("active");
      $(".choose-theme li[title='black']").addClass("active");
      $(".selectgroup-input[value='2']").prop("checked", true);
    } else {
      // Full Black layout
      body.removeClass().addClass("fullblack dark-sidebar theme-white");
      body.removeClass("brand-inverse"); // default fullblack is not inverse
      $(".choose-theme li").removeClass("active");
      $(".choose-theme li[title='white']").addClass("active");
      $(".selectgroup-input[value='3']").prop("checked", true);
    }
    persistNow();
  });

  // restore defaults
  $(".btn-restore-theme").on("click", function () {
    $("body").removeClass();
    // restore to Full Black defaults (align with DEFAULT_THEME)
    $("body").addClass("fullblack dark-sidebar theme-white");
    $(".choose-theme li").removeClass("active");
    $(".choose-theme li[title='white']").addClass("active");
    $(".select-layout[value='3']").prop("checked", true);
    $(".select-sidebar[value='2']").prop("checked", true);
    toggle_sidebar_mini(false);
    $("#mini_sidebar_setting").prop("checked", false);
    $("#sticky_header_setting").prop("checked", true);
    toggle_sticky_header(true);
    try { localStorage.removeItem(THEME_KEY); } catch (e) {}
  });

  // ------------ APPLY SAVED THEME (or default) ------------
  applySavedOrDefault();
});
