(function () {
  if (typeof window.ffbbCartLive !== 'undefined') return;
  window.ffbbCartLive = true;

  var ajaxUrl = typeof ffbb_cart_vars !== 'undefined' && ffbb_cart_vars.ajaxurl
    ? ffbb_cart_vars.ajaxurl
    : (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');

  function fetchCartData() {
    return fetch(ajaxUrl + '?action=ffbb_cart_data', {
      credentials: 'same-origin'
    }).then(function (r) { return r.json(); });
  }

  function updateCart(e) {
    fetchCartData().then(function (resp) {
      if (!resp || !resp.success) return;
      var d = resp.data;
      var items = d.items || [];

      var currentIds = items.map(function (it) { return String(it.id); });

      updateCartToken('cart_total', d.total);
      updateCartToken('cart_subtotal', d.subtotal);
      updateCartToken('cart_item_count', d.item_count);

      items.forEach(function (item) {
        var id = String(item.id);

        updateItemToken('price', id, item.price_formatted);
        updateItemToken('subtotal', id, item.subtotal_formatted);

        var qtyInputs = document.querySelectorAll(
          '[data-ffbb-item-id="' + id + '"] .qty-value[data-fluent-cart-cart-list-item-quantity-input]'
        );
        qtyInputs.forEach(function (input) { input.value = item.quantity; });
      });

      document.querySelectorAll('[data-ffbb-item-id]').forEach(function (el) {
        var id = el.getAttribute('data-ffbb-item-id');
        if (!id) return;
        if (currentIds.indexOf(id) === -1) {
          el.classList.add('ffbb-item-stale');
        }
      });
    }).catch(function () {});
  }

  function updateItemToken(token, itemId, value) {
    var selector = '[data-ffbb-token="' + token + '"][data-ffbb-item-id="' + itemId + '"]';
    document.querySelectorAll(selector).forEach(function (el) {
      if (el.tagName === 'INPUT') {
        el.value = value;
      } else {
        el.textContent = value;
      }
    });
  }

  function updateCartToken(token, value) {
    var selector = '[data-ffbb-token="' + token + '"]';
    document.querySelectorAll(selector).forEach(function (el) {
      el.textContent = value;
    });
  }

  document.addEventListener('fluentCartFragmentsReplaced', updateCart);
  document.addEventListener('fluentCartNotifyCartDrawerItemChanged', updateCart);
})();
