const assert = require('node:assert/strict');
const { seasonAt } = require('../../public/js/weather-seasons.js');
for (const [date, expected] of [
    ['2026-02-28T22:59:00Z', 'winter'], ['2026-02-28T23:00:00Z', 'spring'],
    ['2026-05-31T21:59:00Z', 'spring'], ['2026-05-31T22:00:00Z', 'summer'],
    ['2026-08-31T21:59:00Z', 'summer'], ['2026-08-31T22:00:00Z', 'autumn'],
    ['2026-11-30T22:59:00Z', 'autumn'], ['2026-11-30T23:00:00Z', 'winter'],
    ['2027-01-01T00:00:00Z', 'winter'],
]) assert.equal(seasonAt(new Date(date)), expected, date);
console.log('Seizoenen geslaagd: alle grenzen op Nederlandse lokale tijd, zomertijd en jaarwisseling.');
