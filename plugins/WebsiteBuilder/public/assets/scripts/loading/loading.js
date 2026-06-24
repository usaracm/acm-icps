var Loading = (function () {
    'use strict';

    class Loading {
        constructor(opts = {}) {
            let defaults = {
            onReady: () => {}
            };
            this.opts = Object.assign(this, defaults, opts);

            let showProgress = false;
            const NAMESPACE = 'builderstart';

            localStorage.removeItem(NAMESPACE); //clear (always displayed)
            
            const TIMESTAMP = Date.now();
            if (!JSON.parse(localStorage.getItem(NAMESPACE))) {
                // first time
                showProgress = true;

                localStorage.setItem(NAMESPACE, JSON.stringify({
                    initial: TIMESTAMP,
                    expiresOn: TIMESTAMP + 1000*60*60*24*30 // 1month in ms, 3*60*1000 (3 min)
                }));
            } else {
                // next time, check:
                const EXPIRE_DATE = JSON.parse(localStorage.getItem(NAMESPACE)).expiresOn;
                if (Date.now() > EXPIRE_DATE) {
                    // console.log('session expired');
                    showProgress = true;

                    // reset
                    localStorage.setItem(NAMESPACE, JSON.stringify({
                        initial: TIMESTAMP,
                        expiresOn: TIMESTAMP + 1000*60*60*24*30 // 1month in ms, 3*60*1000 (3 min)
                    }));
                    
                } else {
                    // Do Nothing
                    // do not show progress
                }
            }

            let loadingStatus = document.querySelector('.page-loading');
            if (!loadingStatus && showProgress) {
            const html = `
                    <div class="page-loading">
                        <style>
                        #progress-container {
                            width: 100%;
                            height: 3px;
                            background-color: #f0f0f0;
                            position: fixed;
                            top: 0;
                            left: 0;
                            z-index: 1000;
                        }
                    
                        #progress-bar {
                            height: 100%;
                            background-color: #444;
                            width: 0;
                        }
                        </style>
                        <div id="progress-container" style="display:none">
                            <div id="progress-bar"></div>
                        </div>
                    </div>
                    `;
            document.body.insertAdjacentHTML('beforeend', html);
            loadingStatus = document.querySelector('#loadingStatus');
            this.loadingStatus = loadingStatus;

            setTimeout(()=>{
                let progressContainer = document.querySelector('#progress-container');
                if(progressContainer) progressContainer.style.display = '';
            }, 0);

            window.onload = function () {
                let loadingStatus = document.querySelector('.page-loading');
                if(loadingStatus) loadingStatus.parentNode.removeChild(loadingStatus);
            };
        
            var progressBar = document.getElementById("progress-bar");
            var maxProgress = 100;
            var currentProgress = 0;
    
            var interval = setInterval(function () {
            currentProgress += 1;
            progressBar.style.width = (currentProgress / maxProgress) * 100 + "%";
    
            if (currentProgress >= maxProgress) {
                clearInterval(interval);

                let loadingStatus = document.querySelector('.page-loading');
                if(loadingStatus) loadingStatus.parentNode.removeChild(loadingStatus);
            }
            }, 200);
        }
      }

      stop() {
        let loadingStatus = document.querySelector('.page-loading');
        if(loadingStatus) loadingStatus.parentNode.removeChild(loadingStatus);
      }

    }

    return Loading;

})();
