function byId(id) {
  return document.getElementById(id);
}

function ready(fn) {
  if (document.readyState === 'complete' || document.readyState === 'interactive') {
    fn();
  } else {
    document.addEventListener('DOMContentLoaded', fn);
  }
}

ready(function () {
  var toggle = byId('navToggle');
  var nav = byId('appNav');
  if (toggle && nav) {
    toggle.onclick = function () {
      if (nav.className.indexOf('open') >= 0) {
        nav.className = nav.className.replace('open', '').replace(/\s+/g, ' ');
      } else {
        nav.className += ' open';
      }
    };
  }

  var cat = byId('category_id');
  var type = byId('type_id');
  var title = byId('title');
    if (cat && type) {
    cat.onchange = function () {
      loadTypes(cat.value, type, '', title);
    };
    type.onchange = function () {
      if (title && title.value === '' && type.selectedIndex > 0) {
        title.value = type.options[type.selectedIndex].text;
      }
    };
    if (cat.value) {
      loadTypes(cat.value, type, type.getAttribute('data-selected') || '', title);
    }
  }

  var themeBtn = byId('themeToggle');
  if (themeBtn) {
    themeBtn.onclick = function () {
      var root = document.documentElement;
      var dark = root.className.indexOf('dark') >= 0;
      if (dark) {
        root.className = root.className.replace('dark', '').replace(/\s+/g, ' ').replace(/^\s|\s$/g, '');
        try { localStorage.setItem('snk-theme', 'light'); } catch (e) {}
      } else {
        root.className = (root.className + ' dark').replace(/^\s+/, '');
        try { localStorage.setItem('snk-theme', 'dark'); } catch (e) {}
      }
    };
  }

  enhancePasswordFields();

  refreshPending();
  setInterval(refreshPending, 45000);
});

function enhancePasswordFields() {
  var inputs = document.getElementsByTagName('input');
  var list = [];
  var i;
  for (i = 0; i < inputs.length; i++) {
    if (inputs[i].type === 'password') {
      list.push(inputs[i]);
    }
  }
  for (i = 0; i < list.length; i++) {
    wrapPasswordInput(list[i]);
  }
}

function wrapPasswordInput(input) {
  if (!input || !input.parentNode) {
    return;
  }
  if (input.parentNode.className && input.parentNode.className.indexOf('password-wrap') >= 0) {
    return;
  }
  var wrap = document.createElement('div');
  wrap.className = 'password-wrap';
  input.parentNode.insertBefore(wrap, input);
  wrap.appendChild(input);
  var btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'password-toggle';
  btn.setAttribute('aria-label', 'แสดงรหัสผ่าน');
  btn.appendChild(document.createTextNode('แสดง'));
  wrap.appendChild(btn);
  btn.onclick = function () {
    if (input.type === 'password') {
      input.type = 'text';
      btn.innerHTML = 'ซ่อน';
      btn.setAttribute('aria-label', 'ซ่อนรหัสผ่าน');
    } else {
      input.type = 'password';
      btn.innerHTML = 'แสดง';
      btn.setAttribute('aria-label', 'แสดงรหัสผ่าน');
    }
  };
}

function loadTypes(categoryId, typeSelect, selected, titleInput) {
  if (!categoryId) {
    typeSelect.innerHTML = '<option value="">เลือกหมวดก่อน</option>';
    return;
  }
  var xhr = new XMLHttpRequest();
  xhr.open('GET', 'index.php?p=ajax&action=types&category_id=' + encodeURIComponent(categoryId), true);
  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4 && xhr.status === 200) {
      var rows = [];
      try { rows = JSON.parse(xhr.responseText); } catch (e) { rows = []; }
      var html = '<option value="">เลือกประเภทงาน</option>';
      var i;
      var sel = selected || typeSelect.getAttribute('data-selected') || '';
      for (i = 0; i < rows.length; i++) {
        html += '<option value="' + rows[i].id + '"' + (String(rows[i].id) === String(sel) ? ' selected' : '') + '>' + rows[i].name + '</option>';
      }
      typeSelect.innerHTML = html;
    }
  };
  xhr.send(null);
}

function refreshPending() {
  var el = byId('pendingCount');
  if (!el) { return; }
  var xhr = new XMLHttpRequest();
  xhr.open('GET', 'index.php?p=ajax&action=stats', true);
  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4 && xhr.status === 200) {
      try {
        var data = JSON.parse(xhr.responseText);
        if (data && typeof data.pending !== 'undefined') {
          el.innerHTML = data.pending;
        }
      } catch (e) {}
    }
  };
  xhr.send(null);
}
