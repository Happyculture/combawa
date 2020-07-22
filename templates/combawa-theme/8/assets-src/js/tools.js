(function (Drupal, document) {

  /**
   * Filters out the list so any item has never been called with this id.
   *
   * @param NodeList|array list
   * @param string id
   * @returns array
   */
  Drupal.once = (list, id) => {
    const key = 'data-once-' + id;
    const filtered = Array.prototype.filter.call(list, (item) => {
      return !item.hasAttribute(key);
    });
    filtered.forEach((item) => {
      item.setAttribute(key, 'true');
    });
    return filtered;
  };

  /**
   * Gets all element ancestors that matches the selector.
   *
   * @param HTMLElement el
   * @param string selector
   * @returns array
   */
  Drupal.parents = (el, selector) => {
    let current = el,
      list = [];
    while(current.parentNode != null && current.parentNode !== document.documentElement) {
      if (selector.length === 0 || current.parentNode.matches(selector)) {
        list.push(current.parentNode);
      }
      current = current.parentNode;
    }
    return list
  };

})(Drupal, document);
