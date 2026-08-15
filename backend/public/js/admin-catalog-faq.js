/**
 * EasyAdmin removes FAQ rows from the DOM, but remaining items keep their original
 * numeric indices (e.g. 1,2,3 after deleting 0). Renumber before submit so Symfony
 * receives a contiguous catalogFaq[0..n-1] collection.
 */
(function () {
  function collectionItems(collection) {
    return Array.from(collection.querySelectorAll(':scope .field-collection-item')).filter(function (item) {
      return item.closest('[data-ea-collection-field]') === collection;
    });
  }

  function reindexCollection(collection) {
    var items = collectionItems(collection);
    items.forEach(function (item, index) {
      item.querySelectorAll('input[name], textarea[name], select[name]').forEach(function (field) {
        field.name = field.name.replace(/(\[catalogFaq\])\[\d+\]/, '$1[' + index + ']');
        field.id = field.id.replace(/_catalogFaq_\d+_/, '_catalogFaq_' + index + '_');
      });

      item.querySelectorAll('label[for]').forEach(function (label) {
        label.htmlFor = label.htmlFor.replace(/_catalogFaq_\d+_/, '_catalogFaq_' + index + '_');
      });
    });

    collection.dataset.numItems = String(items.length);
  }

  function findCollections(root) {
    return root.querySelectorAll('[data-ea-collection-field="catalogFaq"], [data-ea-collection-field="true"]');
  }

  document.addEventListener(
    'submit',
    function (event) {
      var form = event.target;
      if (!(form instanceof HTMLFormElement)) {
        return;
      }

      findCollections(form).forEach(reindexCollection);
    },
    true
  );

  document.addEventListener('ea.collection.item-removed', function (event) {
    var collection = event.detail && event.detail.collection;
    if (collection) {
      reindexCollection(collection);
    }
  });
})();
