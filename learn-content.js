(function () {
  function setText(selector, value) {
    var element = document.querySelector(selector);
    if (element && typeof value === 'string') {
      element.textContent = value;
    }
  }

  function renderParagraphs(element, text) {
    if (!element) {
      return;
    }

    element.innerHTML = '';
    String(text || '').split(/\n\s*\n/).forEach(function (paragraph) {
      if (!paragraph.trim()) {
        return;
      }
      var p = document.createElement('p');
      p.textContent = paragraph.trim();
      element.appendChild(p);
    });
  }

  fetch('/api/learn-content.php', { cache: 'no-store' })
    .then(function (response) {
      if (!response.ok) {
        throw new Error('Unable to load Learn content');
      }
      return response.json();
    })
    .then(function (data) {
      var sections = Array.isArray(data.sections) ? data.sections : [];
      var list = document.querySelector('[data-learn-list]');

      if (list) {
        list.innerHTML = '';
        sections.forEach(function (section) {
          var link = document.createElement('a');
          link.className = 'lesson learn-section-link';
          link.href = '/learn-' + section.id + '.html';

          var number = document.createElement('div');
          number.className = 'num';
          number.textContent = section.number;

          var text = document.createElement('div');
          var title = document.createElement('h3');
          title.textContent = section.title;
          var summary = document.createElement('p');
          summary.textContent = section.summary;
          text.appendChild(title);
          text.appendChild(summary);

          var tag = document.createElement('span');
          tag.className = 'tag';
          tag.textContent = section.level;

          link.appendChild(number);
          link.appendChild(text);
          link.appendChild(tag);
          list.appendChild(link);
        });
      }

      var lessonPage = document.querySelector('[data-learn-id]');
      if (lessonPage) {
        var id = lessonPage.getAttribute('data-learn-id');
        var section = sections.find(function (item) {
          return item.id === id;
        });

        if (!section) {
          return;
        }

        setText('[data-lesson-number]', section.number);
        setText('[data-lesson-title]', section.title);
        setText('[data-lesson-summary]', section.summary);
        setText('[data-lesson-level]', section.level);
        setText('[data-breadcrumb-title]', section.title);
        document.title = section.title + ' | Sugar Code It';
        renderParagraphs(document.querySelector('[data-lesson-content]'), section.content);
      }
    })
    .catch(function () {
      // Keep the page usable if the server content cannot be loaded.
    });
})();
