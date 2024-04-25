/* eslint-disable no-console */
/* Reads messages.yml and generates PHP files from it */
const read = require('read-yaml');
const fs = require('fs');

console.log(`converting ${process.argv[2]} ...`);
console.log(`storing results in dir ${process.argv[3]} ...`);

const messages = read.sync(process.argv[2]);
const targetDir = process.argv[3];
const langs = ['de', 'en', 'es', 'fr', 'gl', 'hu', 'it', 'nl','pl'];

langs.forEach(lang => {
    data = [];
    data.push("<?php\n");
    data.push("# Generated file. Please don\'t edit here,");
    data.push("# edit messages.yml instead.");
    data.push("#");
    data.push(`$messages = [`);
    Object.entries(messages).forEach( msgArr => {
        const msg = msgArr[0];
        const locs = msgArr[1];
        if (locs[lang] !== undefined) {
            data.push(`    \'${msg}\' => \'${locs[lang]}\',`);
        }
    });
    data.push("];\n");
    data.push(`return $messages;`);
    data.push("");
    fs.writeFileSync(`${targetDir}/messages.${lang}.php`, data.join("\n"));
});
