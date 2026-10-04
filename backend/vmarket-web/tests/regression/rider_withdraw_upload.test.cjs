const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const path = require('node:path');

async function exercise(actor, valid) {
  const callbacks = {};
  let submitted;
  let reported = false;
  const proof = {name: 'transfer.pdf', bytes: 'evidence'};
  const form = {proof, checkValidity: () => valid, reportValidity: () => {reported = true;}};
  function jquery(selector) {
    const item = {on(event, callback) {callbacks[`${selector}:${event}`] = callback; return item;},
      data(key) {return key === 'form-id' ? 'approval-note-form' : 'Confirm';},
      attr() {return '/rider/withdraw/status';}, val() {return 'pending';},
      closest() {return item;}, fadeIn() {}, fadeOut() {}, addClass() {}, removeClass() {},
      html() {return item;}, empty() {return item;}};
    return item;
  }
  jquery.ajaxSetup = () => {};
  jquery.post = (request) => {submitted = request;};
  class UploadData {constructor(target) {this.proof = target.proof;}}
  const context = vm.createContext({$: jquery, document: {getElementById: () => form},
    FormData: UploadData, Swal: {fire: () => Promise.resolve({value: true})},
    location: {reload() {}}, toastMagic: {error() {}, success() {}}, setTimeout});
  const source = fs.readFileSync(path.join(__dirname, '../../public/assets/back-end/js', actor, 'withdraw.js'), 'utf8');
  vm.runInContext(source, context);
  context.formSubmit();
  callbacks['.form-submit:click'].call({});
  await new Promise(resolve => setImmediate(resolve));
  if (valid) {
    assert.equal(submitted.data.proof, proof, `${actor} dropped proof file`);
    assert.equal(submitted.contentType, false);
    assert.equal(submitted.processData, false);
  } else {
    assert.equal(submitted, undefined, `${actor} submitted invalid form`);
    assert.equal(reported, true);
  }
}
(async () => {
  for (const actor of ['admin', 'vendor']) {
    await exercise(actor, true);
    await exercise(actor, false);
  }
  console.log('4 actual rider approval JavaScript upload regressions passed');
})().catch(error => {console.error(error); process.exitCode = 1;});
