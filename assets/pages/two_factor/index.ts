import Page from '@wexample/symfony-loader/js/Class/Page';

// The button sending a new code, held while a code has just been sent: let go
// once the wait the server gave is over, without the page being reloaded.
export default class extends Page {
  private resendTimer?: number;

  async pageReady() {
    const button = this.el.querySelector<HTMLButtonElement>('[data-resend-wait]');
    const wait = Number(button?.dataset.resendWait);

    if (!button || !wait) {
      return;
    }

    this.resendTimer = window.setTimeout(() => {
      button.disabled = false;
      button.removeAttribute('title');
    }, wait * 1000);
  }
}
