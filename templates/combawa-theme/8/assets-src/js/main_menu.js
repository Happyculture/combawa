(function (Drupal, debounce, document, window) {

  /**
   * Close all open submenus by simulating a click on their button.
   *
   * @param event
   */
  const closeAll = () => {
    document.querySelectorAll('.menu__child__trigger[aria-expanded="true"]')
      .forEach((openEl) => {
        const event = new MouseEvent('click', {
          view: window,
          bubbles: true,
          cancelable: true
        });
        openEl.dispatchEvent(event);
      });
  };

  /**
   * Function called when a trigger button is clicked.
   *
   * Show or hide the submenu according to the button state.
   *
   * @param event
   */
  const onButtonClick = (event) => {
    const currentTargetEl = event.currentTarget;
    const target = currentTargetEl.nextElementSibling;

    if (currentTargetEl.getAttribute('aria-expanded') === 'true') {
      target.setAttribute('aria-hidden', 'true');
      currentTargetEl.setAttribute('aria-expanded', 'false');
    }
    else {
      // Close other opened menus.
      closeAll();
      // Open the target menu.
      target.removeAttribute('aria-hidden');
      currentTargetEl.setAttribute('aria-expanded', 'true');
    }
    event.stopPropagation();
  };

  Drupal.behaviors.MainMenu = {
    attach: function (context, settings) {
      // Toggle submenus on click on the trigger button.
      const buttons = document.querySelectorAll('.menu__child__trigger');
      Drupal.once(buttons, 'MainMenu').forEach((el) => {
        el.addEventListener('click', onButtonClick);

        // Prevent closing a submenu by clicking on a link inside.
        el.nextElementSibling.addEventListener('click', (event) => {
          event.stopPropagation();
        });
      });

      // Close submenus when any part of the document is clicked.
      const body = document.querySelectorAll('body');
      Drupal.once(body, 'MainMenu').forEach((el) => {
        document.addEventListener('click', closeAll);
      });
    }
  };

})(Drupal, Drupal.debounce, document, window);
