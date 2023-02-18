<?php
defined('ABSPATH') || exit;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <title>二维码登录</title>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <script src="https://cdn.staticfile.org/vue/2.6.14/vue.min.js"></script>
    <script src="https://cdn.staticfile.org/axios/1.3.2/axios.min.js"></script>
    <script src="https://cdn.staticfile.org/qrcode/1.5.1/qrcode.min.js"></script>
    <link rel='stylesheet' id='login-css' href='<?php echo admin_url('/css/login.min.css?ver=6.1.1') ?>'
          media='all'/>
    <style>
        .container {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
        }

        .qr-code-container {
            width: 250px;
            height: 250px;
            margin: 0 auto;
            background: #eeeeee;
            position: relative;
        }

        .state {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            flex-direction: column;
            gap: 10px;
            justify-content: center;
            align-items: center;
            font-size: 16px;
            color: #333333;
            font-weight: bold;
        }

        .state3 {
            cursor: pointer;
        }

    </style>
</head>
<body>
<div class="container login js login-action-login wp-core-ui" id="app">

    <div id="login">
        <p class="message" style="margin-top: 10px;">请使用APP或者小程序扫描二维码登录</p>
        <form name="loginform" id="loginform">
            <!--  输出一个二维码图片-->
            <div class="qr-code-container">
                <div class="state" v-if="state === 1">
                    <svg t="1676728485364" class="icon" viewBox="0 0 1024 1024" version="1.1"
                         xmlns="http://www.w3.org/2000/svg" p-id="2776" width="50" height="50">
                        <path d="M512 1024a512 512 0 1 1 0-1024 512 512 0 0 1 0 1024z m-71.318588-361.411765a29.334588 29.334588 0 0 0 20.48-8.252235L774.625882 349.364706a27.708235 27.708235 0 0 0 0-39.936 29.575529 29.575529 0 0 0-41.08047 0l-292.74353 284.912941L290.454588 448.150588a29.575529 29.575529 0 0 0-41.08047 0 27.708235 27.708235 0 0 0 0 39.996236l170.706823 166.128941a29.274353 29.274353 0 0 0 20.540235 8.252235z"
                              fill="#118FFF" p-id="2777"></path>
                    </svg>
                    <div style="text-align: center">
                        <div>
                            扫描成功
                        </div>
                        <div>
                            请在手机端确认登录
                        </div>
                    </div>
                </div>
                <div class="state state3" v-if="state === 3" @click="fetchQRCode">
                    <svg t="1676728858122" class="icon" viewBox="0 0 1132 1024" version="1.1"
                         xmlns="http://www.w3.org/2000/svg" p-id="7204" width="50" height="50">
                        <path d="M1097.688802 515.053607q9.215671 0 16.895397 6.655762t11.775579 17.407378 2.559909 24.575122-11.775579 29.182958q-20.479269 30.718903-50.68619 77.821221t-76.285276 103.420307q-13.311525 16.383415-25.599086 22.527196t-27.647013 4.095854q-16.383415-2.047927-25.599086-14.335488t-24.575122-27.647013q-22.527196-22.527196-43.518446-49.662227t-38.398629-52.222135-29.69494-44.542409-16.383415-26.623049q-10.239634-15.359452-11.775579-28.670976t2.047927-22.527196 10.239634-14.335488 13.823506-5.119817l104.44427 0q0-74.749331-30.206921-147.962716t-86.52491-129.531374q-52.222135-52.222135-119.803722-77.309239t-138.235064-24.063141-137.723082 27.647013-119.29174 78.845184-81.405093 121.851649-32.254848 143.354881 20.99125 142.842899 77.309239 119.29174q47.102318 43.006464 94.204636 68.093568t92.156709 36.350702 86.012928 10.239634 75.261312-9.215671 60.925824-22.015214 41.982501-28.158994q24.575122-14.335488 52.222135-15.359452t48.126281 20.479269q25.599086 25.599086 18.431342 52.734117t-31.742866 51.710153q-11.263598 11.263598-25.599086 17.407378-67.581587 48.126281-146.426771 63.485733t-158.714332 2.047927-155.642442-52.734117-136.187137-99.836435q-73.725367-73.725367-110.588051-166.394058t-36.862684-189.433235 37.374665-189.945217 111.100033-166.90604 166.394058-111.100033 189.433235-37.374665 189.945217 37.374665 166.90604 111.100033q40.958537 40.958537 69.117532 87.036892t45.054391 93.692654 24.063141 94.204636 7.167744 87.548874l93.180673 0zM577.515377 513.005681q0 26.623049-18.431342 45.566373t-45.054391 18.943324-45.566373-18.943324-18.943324-45.566373l0-192.505126q0-26.623049 18.943324-45.054391t45.566373-18.431342 45.054391 18.431342 18.431342 45.054391l0 192.505126zM514.029644 641.00111q26.623049 0 45.054391 18.943324t18.431342 45.566373-18.431342 45.054391-45.054391 18.431342-45.566373-18.431342-18.943324-45.054391 18.943324-45.566373 45.566373-18.943324z"
                              p-id="7205" fill="#d81e06"></path>
                    </svg>
                    <div style="text-align: center">
                        <div>
                            二维码失效
                        </div>
                        <div>
                            点击刷新二维码
                        </div>
                    </div>
                </div>

                <img style="height: 100%;width: 100%;" v-if="qrcodeImg" :src="qrcodeImg" alt="二维码">
            </div>
        </form>
        <p id="backtoblog">
            <a href="<?php echo site_url('wp-login.php') ?>">&larr; 使用账户密码登录</a>
        </p>
    </div>

    <script>
        const sleep = time => new Promise((resolve) => setTimeout(resolve, time));

        const fetchQRStatus = token => axios.post('<?php echo site_url('/wp-json/uni-app-rest-enhanced/v1/login/getQRStatus') ?>', {
            token
        })
            .then(data => data.data)

        new Vue({
            el: '#app',
            data() {
                return {
                    qrcodeImg: '',
                    token: '',
                    state: 0
                }
            },
            methods: {
                async fetchQRCode() {
                    this.state = 0
                    try {
                        const res = await axios.post('<?php echo site_url('/wp-json/uni-app-rest-enhanced/v1/login/getQRToken') ?>')
                            .then(data => data.data)
                        const arg = JSON.stringify(res)
                        // this.qrcodeImg = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' + encodeURIComponent(arg)
                        this.qrcodeImg = await QRCode.toDataURL(arg, {margin: 0, width: 450})
                        this.token = res.token
                        this.getQRStatus()

                    } catch (e) {
                        console.error(e)
                    }
                },
                async getQRStatus() {
                    while (true) {
                        try {
                            const res = await fetchQRStatus(this.token)
                            if (res.status === 1) {
                                // 扫描成功，显示扫描成功遮罩
                                this.state = 1
                            } else if (res.status === 2) {
                                // 登录成功，重定向到登录前页面
                                window.location.href = '<?php echo site_url($wp->request) ?>'
                                break
                            } else if (res.status === 3) {
                                // 取消登录，显示二维码过期
                                this.showInvalidQRCode()
                                break
                            } else if (res.status === -1) {
                                // 二维码过期，重新获取二维码
                                this.showInvalidQRCode()
                                break
                            }
                            await sleep(1000)
                        } catch (e) {
                            // console.error(e)
                            // 二维码失效或过期，让用户点击重新获取
                            this.showInvalidQRCode()
                            break
                        }
                    }

                },
                showInvalidQRCode() {
                    this.state = 3
                }
            },
            mounted() {
                this.fetchQRCode()
            }
        })
    </script>
</body>
</html>