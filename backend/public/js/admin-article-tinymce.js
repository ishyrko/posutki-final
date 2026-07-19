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
      if (!btn.form.querySelector("textarea.ea-article-content-rte, textarea.ea-static-page-content-rte")) {
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

    document.querySelectorAll("textarea.ea-article-content-rte, textarea.ea-static-page-content-rte").forEach(function (el) {
      if (el.getAttribute("data-tinymce-initialized")) {
        return;
      }
      el.setAttribute("data-tinymce-initialized", "1");

      var uploadScope = el.getAttribute("data-upload-scope");
      var enableImages = uploadScope === "articles" || uploadScope === "static-pages";
      var plugins = enableImages ? "lists link autolink paste image" : "lists link autolink paste";
      var toolbar = enableImages
        ? "undo redo | blocks | bold italic underline strikethrough | bullist numlist | link image | removeformat"
        : "undo redo | blocks | bold italic underline strikethrough | bullist numlist | link | removeformat";
      var contentStyle =
        "body{font-family:system-ui,-apple-system,sans-serif;font-size:15px;line-height:1.6;}" +
        (enableImages
          ? " img{display:block;width:100%;max-width:100%;height:auto;margin:1.5rem 0;border-radius:0.5rem;}"
          : "");

      var editorConfig = {
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
        plugins: plugins,
        paste_as_text: false,
        toolbar: toolbar,
        block_formats: "Paragraph=p; Heading 2=h2; Heading 3=h3",
        content_style: contentStyle,
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

          if (enableImages) {
            editor.on("ObjectResized", function (e) {
              if (e.target && e.target.nodeName === "IMG") {
                editor.dom.setAttribs(e.target, { width: null, height: null, style: null });
              }
            });

            editor.on("SetContent", function () {
              editor.getBody().querySelectorAll("img").forEach(function (img) {
                editor.dom.setAttribs(img, { width: null, height: null, style: null });
              });
            });
          }

          editor.on("change blur keyup Undo Redo", syncToTextarea);
          editor.on("init", syncToTextarea);
        },
      };

      if (enableImages) {
        editorConfig.automatic_uploads = true;
        editorConfig.image_dimensions = false;
        editorConfig.images_upload_credentials = true;
        editorConfig.images_upload_handler = function (blobInfo, progress) {
          return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.withCredentials = true;
            xhr.open("POST", "/admin/upload/image");
            xhr.upload.onprogress = function (event) {
              if (event.lengthComputable) {
                progress((event.loaded / event.total) * 100);
              }
            };
            xhr.onload = function () {
              if (xhr.status < 200 || xhr.status >= 300) {
                reject("Ошибка загрузки (HTTP " + xhr.status + ")");
                return;
              }

              var json;
              try {
                json = JSON.parse(xhr.responseText);
              } catch (parseError) {
                reject("Некорректный ответ сервера");
                return;
              }

              if (!json || typeof json.location !== "string") {
                reject((json && json.error) || "Не удалось загрузить изображение");
                return;
              }

              resolve(json.location);
            };
            xhr.onerror = function () {
              reject("Ошибка сети при загрузке");
            };

            var formData = new FormData();
            formData.append("file", blobInfo.blob(), blobInfo.filename());
            formData.append("scope", uploadScope);
            xhr.send(formData);
          });
        };
      }

      tinymce.init(editorConfig);
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initRichEditors);
  } else {
    initRichEditors();
  }
})();
