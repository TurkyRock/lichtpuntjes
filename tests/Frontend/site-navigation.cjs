const fs = require('fs');
const path = require('path');
const vm = require('vm');
const assert = require('assert/strict');

const source = fs.readFileSync(path.resolve(__dirname, '../../public/js/site-navigation.js'), 'utf8');
function setup(isCompact) {
    const classes = new Set();
    const navigationEvents = {};
    const toggleEvents = {};
    const linkEvents = {};
    const attributes = {};
    let change;
    const compact = { matches: isCompact, addEventListener(type, handler) { change = handler; } };
    const navigation = {
        closest() { return null; },
        classList: { add(name) { classes.add(name); }, toggle(name, active) { active ? classes.add(name) : classes.delete(name); } },
        addEventListener(type, handler) { navigationEvents[type] = handler; },
    };
    const toggle = {
        hidden: true, focused: false,
        setAttribute(name, value) { attributes[name] = value; },
        getAttribute(name) { return attributes[name]; },
        addEventListener(type, handler) { toggleEvents[type] = handler; },
        focus() { this.focused = true; },
    };
    const links = { addEventListener(type, handler) { linkEvents[type] = handler; } };
    vm.runInNewContext(source, {
        document: { querySelector: selector => selector === '.site-nav' ? navigation : null, getElementById: id => id === 'siteMenu' ? links : toggle },
        window: { matchMedia: () => compact, addEventListener() {} },
    });
    return { classes, attributes, toggle, toggleEvents, navigationEvents, linkEvents, resize(matches) { compact.matches = matches; change(); } };
}

const mobile = setup(true);
assert.equal(mobile.toggle.hidden, false);
assert.equal(mobile.attributes['aria-expanded'], 'false');
assert.equal(mobile.classes.has('is-open'), false);
mobile.toggleEvents.click();
assert.equal(mobile.attributes['aria-expanded'], 'true');
assert.equal(mobile.classes.has('is-open'), true);
mobile.navigationEvents.keydown({ key: 'Escape' });
assert.equal(mobile.attributes['aria-expanded'], 'false');
assert.equal(mobile.toggle.focused, true);
mobile.toggleEvents.click();
mobile.linkEvents.click({ target: { closest: () => ({}) } });
assert.equal(mobile.attributes['aria-expanded'], 'false');
mobile.resize(false);
assert.equal(mobile.attributes['aria-expanded'], 'true');
mobile.resize(true);
assert.equal(mobile.attributes['aria-expanded'], 'false');

const desktop = setup(false);
assert.equal(desktop.attributes['aria-expanded'], 'true');
desktop.navigationEvents.keydown({ key: 'Escape' });
assert.equal(desktop.classes.has('is-open'), true);
vm.runInNewContext(source, { document: { querySelector: () => null, getElementById: () => null } });
console.log('Navigatie geslaagd: mobiel openen en sluiten, Escape, toetsenbordfocus, links, schermwissels en desktop.');
