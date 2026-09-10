/**
 * HomeEase Unified Geolocation & Median.co Bridge Helper
 */
(function(window) {
  'use strict';

  function isMedianApp() {
    return !!(
      (window.median && window.median.geolocation) ||
      (window.gonative && window.gonative.geolocation) ||
      /gonative|median/i.test(navigator.userAgent)
    );
  }

  function getDeviceLocation(options, onSuccess, onError) {
    options = options || {};
    var enableHighAccuracy = options.enableHighAccuracy !== false;
    var timeout = options.timeout || 15000;
    var maximumAge = options.maximumAge || 0;

    // Check Median JS Bridge first if available
    var medianObj = (window.median && window.median.geolocation) ? window.median.geolocation :
                    (window.gonative && window.gonative.geolocation) ? window.gonative.geolocation : null;

    if (medianObj && typeof medianObj.location === 'function') {
      try {
        medianObj.location({
          enableHighAccuracy: enableHighAccuracy,
          callback: function(data) {
            if (data && typeof data.latitude === 'number' && typeof data.longitude === 'number') {
              onSuccess({
                coords: {
                  latitude: data.latitude,
                  longitude: data.longitude,
                  accuracy: data.accuracy || 10,
                  heading: data.heading || null,
                  speed: data.speed || null,
                  altitude: data.altitude || null
                },
                timestamp: Date.now(),
                source: 'median'
              });
              return;
            }
            fallbackToWebGps();
          }
        });
        return;
      } catch (e) {
        console.warn('Median JS Bridge call failed, falling back to navigator.geolocation:', e);
      }
    }

    fallbackToWebGps();

    function fallbackToWebGps() {
      if (!navigator.geolocation) {
        if (onError) {
          onError({
            code: 0,
            message: 'Geolocation is not supported by this browser or app.',
            isMedian: isMedianApp()
          });
        }
        return;
      }

      navigator.geolocation.getCurrentPosition(
        function(pos) {
          if (onSuccess) onSuccess(pos);
        },
        function(err) {
          var customErr = {
            code: err.code,
            message: getFriendlyErrorMessage(err.code),
            originalError: err,
            isMedian: isMedianApp()
          };
          if (onError) onError(customErr);
        },
        {
          enableHighAccuracy: enableHighAccuracy,
          timeout: timeout,
          maximumAge: maximumAge
        }
      );
    }

    function getFriendlyErrorMessage(code) {
      switch (code) {
        case 1: // PERMISSION_DENIED
          if (isMedianApp()) {
            return 'Location permission denied. Enable Location in Median app settings and phone settings.';
          }
          return 'Permission denied — enable location in your browser settings.';
        case 2: // POSITION_UNAVAILABLE
          return 'Location unavailable. Check your GPS signal.';
        case 3: // TIMEOUT
          return 'Location timed out. Please try again.';
        default:
          return 'Could not detect location.';
      }
    }
  }

  function watchDeviceLocation(options, onSuccess, onError) {
    options = options || {};
    getDeviceLocation(options, onSuccess, onError);

    if (navigator.geolocation && typeof navigator.geolocation.watchPosition === 'function') {
      return navigator.geolocation.watchPosition(
        function(pos) { if (onSuccess) onSuccess(pos); },
        function(err) {
          if (onError) onError({
            code: err.code,
            message: err.message,
            isMedian: isMedianApp()
          });
        },
        options
      );
    }
    return null;
  }

  window.HomeEaseLocation = {
    isMedianApp: isMedianApp,
    getLocation: getDeviceLocation,
    watchLocation: watchDeviceLocation
  };
})(window);
