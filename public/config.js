/**
 * 前后端分离 - 前端运行时配置
 * 部署后只改这个文件即可，不必重新编译 H5
 *
 * apiBase  : 后端 API 根地址，注意以 /api 结尾（不要末尾再加斜杠）
 * fileBase : 上传文件/图片所在后端域名（一般与 API 同域，不要带 /api）
 */
window.SITE_CONFIG = {
  apiBase: 'https://api.example.com/api',
  fileBase: 'https://api.example.com'
};
