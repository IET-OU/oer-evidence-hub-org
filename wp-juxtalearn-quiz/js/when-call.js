  // https://gist.github.com/nfreear/f40470e1aec63f442a8a

  /**
  * " When X is true ..., call Y ... "
  *
  * The `when_call` function - loosely coupled detection of state/dependency changes.
  */

  function when_call(when_true_FN, callback_FN, interval) {
    var int_id = setInterval(function () {
      if (when_true_FN()) {
        clearInterval(int_id);
        callback_FN();
      }
    }, interval || 100); // Milliseconds.
  }
