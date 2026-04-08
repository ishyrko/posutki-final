/**
 * TinyMCE for article content in EasyAdmin (loaded after tinymce.min.js).
 * Keeps the underlying <textarea> in sync so HTML5 required and POST body stay correct.
 *
 * Why `<p>&lt;h2&gt;...` used to end up in the DB:
 * - default entity_encoding "named" can serialize angle brackets as entities;
 * - paste from Word/Google/browser sometimes supplies HTML as text (with &lt;), which lands inside <p> as text.
 * Mitigation: entity_encoding "raw", paste plugin, decode on paste and on first load from textarea.
 */
(function () {
  function hasEntityEncodedTags(s) {
    return /&lt;[a-z][\s\S]*?&gt;/i.test(s) || /&lt;\/[a-z]/i.test(s);
  }

  function decodeHtmlEntitiesDeep(s) {
    var prev = "";
    var out = s;
    while (out !== prev) {
      prev = out;
      out = out
        .replace(/&#(\d+);/g, function (_, code) {
          return String.fromCharCode(Number(code));
        })
        .replace(/&#x([0-9a-f]+);/gi, function (_, hex) {
          return String.fromCharCode(parseInt(hex, 16));
        })
        .replace(/&lt;/gi, "<")
        .replace(/&gt;/gi, ">")
        .replace(/&quot;/g, '"')
        .replace(/&#39;/g, "'")
        .replace(/&amp;/g, "&");
    }
    return out;
  }

  function normalizeEditorHtml(html) {
    if (!html || !hasEntityEncodedTags(html)) {
      return html;
    }
    return decodeHtmlEntitiesDeep(html);
  }

  function triggerSaveAll() {
    if (typeof tinymce === "undefined") {
      return;
    }
    tinymce.triggerSave();
  }

  /** Runs before HTML5 validation (submit may not fire if invalid). */
  document.addEventListener(
    "click",
    function (e) {
      var el = e.target;
      if (!el || !el.closest) {
        return;
      }
      var btn = el.closest('button[type="submit"], input[type="submit"]');
      if (!btn || !btn.form) {
        return;
      }
      if (!btn.form.querySelector("textarea.ea-article-content-rte")) {
        return;
      }
      triggerSaveAll();
    },
    true
  );

  document.addEventListener("submit", function () {
    triggerSaveAll();
  }, true);

  function initRichEditors() {
    if (typeof tinymce === "undefined") {
      return;
    }

    document.querySelectorAll("textarea.ea-article-content-rte").forEach(function (el) {
      if (el.getAttribute("data-tinymce-initialized")) {
        return;
      }
      el.setAttribute("data-tinymce-initialized", "1");

      tinymce.init({
        target: el,
        height: 480,
        menubar: false,
        license_key: "gpl",
        branding: false,
        promotion: false,
        /** Keep real angle brackets in tags when saving to textarea */
        entity_encoding: "raw",
        convert_urls: false,
        relative_urls: false,
        /** Rich paste from Word/HTML, not plain-text only */
        plugins: "lists link autolink paste",
        paste_as_text: false,
        toolbar:
          "undo redo | blocks | bold italic underline strikethrough | bullist numlist | link | removeformat",
        block_formats: "Paragraph=p; Heading 2=h2; Heading 3=h3",
        content_style:
          "body{font-family:system-ui,-apple-system,sans-serif;font-size:15px;line-height:1.6;}",
        setup: function (editor) {
          function syncToTextarea() {
            editor.save();
          }

          /** Paste: expand entity-escaped markup into real tags when needed */
          editor.on("PastePreProcess", function (e) {
            if (e.content) {
              var next = normalizeEditorHtml(e.content);
              if (next !== e.content) {
                e.content = next;
              }
            }
          });

          /** Load from textarea (including legacy DB content): normalize to real HTML once */
          editor.on("BeforeSetContent", function (e) {
            if (e.content && typeof e.content === "string") {
              var next = normalizeEditorHtml(e.content);
              if (next !== e.content) {
                e.content = next;
              }
            }
          });

          editor.on("change blur keyup Undo Redo", syncToTextarea);
          editor.on("init", syncToTextarea);
        },
      });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initRichEditors);
  } else {
    initRichEditors();
  }
})();
