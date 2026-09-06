function check(checked = true) {
    const cbs = document.querySelectorAll('input[name="color"]');
    cbs.forEach((cb) => {
        cb.checked = checked;
    });
}

function checkAll() {
    check();
    // reassign 
    this.onclick = uncheckAll;
}

function uncheckAll() {
    check(false);
    this.onclick = checkAll;
}

const btn = document.querySelector('#btn');
btn.onclick = checkAll;