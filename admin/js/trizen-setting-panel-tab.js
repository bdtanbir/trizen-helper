


var btns = document.querySelectorAll('.trizen-setting-tabs-btn');
var content = document.querySelectorAll('.trizen-setting-tab');

var element = document.getElementsByClassName("trizen-setting-tabs-nav")[0];
element.addEventListener("click", trizenSettingTabCallback);
function trizenSettingTabCallback(e) {
    var elems = document.querySelector(".active");
    if (elems != null) {
        elems.classList.remove("active");
    }
    e.target.classList.add('active');
}

document.addEventListener('click', ({ target: { dataset: { id = '' } } }) => {
    if (id.length > 0) {
        document.querySelectorAll('.trizen-setting-tab').forEach(t => t.classList.add('hidden'));
        document.querySelector(`#${id}`).classList.remove('hidden');
    }
});