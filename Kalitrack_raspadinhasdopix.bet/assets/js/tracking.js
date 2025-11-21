const Tracking = (function() {
  function getURLParameters() {
    const params = new URLSearchParams(window.location.search);
    return {
      click_id:  params.get('click_id')  || '',
      fbclid:    params.get('fbclid')    || '',
      pixel_id:  params.get('pixel_id')  || ''
    };
  }

  function send(event_name, value = 0) {
    const { click_id, fbclid, pixel_id } = getURLParameters();
    if (!pixel_id || (!click_id && !fbclid)) return;

    const body = { pixel_id, event_name, value };
    if (fbclid) body.fbclid = fbclid;
    else        body.click_id = click_id;

    fetch('/api/tracking_events.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer track123456'
      },
      body: JSON.stringify(body)
    })
    .then(r => r.json())
    .then(json => console.log(event_name, 'sent:', json))
    .catch(err => console.error('Error sending', event_name, err));
  }

  document.addEventListener('DOMContentLoaded', () => {
    send('EVENT_CONTENT_VIEW', 0);
  });

  return {
    trackAddToCart:       (value=0) => send('EVENT_ADD_TO_CART', value),
    trackRegistration:    (value=0) => send('EVENT_COMPLETE_REGISTRATION', value),
    trackPurchase:        (value=0) => send('EVENT_PURCHASE', value)
  };
})();
