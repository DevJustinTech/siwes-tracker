/* Quiz widget for teach-workspace lessons.
 *
 * Markup contract (keep exactly this shape in lesson HTML):
 *
 *   <div class="quiz"
 *        data-answer="2"            ; 0-based index of correct option
 *        data-why="Because ...">    ; feedback shown after answering
 *     <p class="quiz-q">Question?</p>
 *     <button class="quiz-option">option one</button>
 *     <button class="quiz-option">option two</button>
 *     ...
 *   </div>
 *
 * Behaviour: click = immediate feedback. Correct answer locks the
 * question; a wrong answer strikes itself out and lets you retry
 * (with the score remembering the miss). All option texts in one
 * question should have equal word counts — no formatting tells.
 */
(function () {
  'use strict';

  function init(root) {
    root.querySelectorAll('.quiz').forEach(function (quiz, qi) {
      var answer = parseInt(quiz.dataset.answer, 10);
      var why = quiz.dataset.why || '';
      var options = Array.prototype.slice.call(
        quiz.querySelectorAll('.quiz-option')
      );
      var fb = document.createElement('p');
      fb.className = 'quiz-feedback';
      quiz.appendChild(fb);

      var done = false;
      var missed = false;

      options.forEach(function (btn, idx) {
        btn.type = 'button';
        btn.addEventListener('click', function () {
          if (done) return;
          if (idx === answer) {
            done = true;
            btn.classList.add('quiz-correct');
            if (!missed) record(qi, true);
            fb.textContent = 'Correct. ' + why;
            fb.style.color = 'var(--accent)';
            options.forEach(function (b) { b.disabled = true; b.style.cursor = 'default'; });
          } else {
            missed = true;
            btn.classList.add('quiz-wrong');
            btn.disabled = true;
            record(qi, false);
            fb.textContent = 'Not quite — try again.';
            fb.style.color = 'var(--bad)';
          }
        });
      });
    });
    renderScore(root);
  }

  /* score: one entry per question, true = first-try correct */
  var results = {};

  function record(qi, ok) {
    if (!(qi in results)) results[qi] = ok;
  }

  function renderScore(root) {
    var total = root.querySelectorAll('.quiz').length;
    if (!total) return;
    var box = document.createElement('div');
    box.className = 'note';
    box.id = 'quiz-score';
    root.appendChild(box);
    var obs = new MutationObserver(function () {
      var answered = Object.keys(results).length;
      var firstTry = Object.values(results).filter(Boolean).length;
      box.textContent = answered === 0
        ? 'Quiz score: ' + total + ' questions. Answer from memory — no scrolling back up.'
        : 'Quiz score: ' + firstTry + '/' + total +
          ' correct on the first try (' + answered + '/' + total + ' answered).';
    });
    obs.observe(root, { subtree: true, childList: true, characterData: true });
    box.textContent = 'Quiz score: ' + total + ' questions. Answer from memory — no scrolling back up.';
  }

  document.addEventListener('DOMContentLoaded', function () {
    init(document.querySelector('.page') || document.body);
  });
})();
