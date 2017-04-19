<?php

namespace Freyo\LaravelEntWechat\Middleware;

use Closure;
use EntWeChat\Foundation\Application;
use Event;
use Freyo\LaravelEntWechat\Events\WeChatUserAuthorized;
use Log;

/**
 * Class OAuthAuthenticate.
 */
class OAuthAuthenticate
{
    /**
     * Use Service Container would be much artisan.
     */
    private $wechat;

    /**
     * Inject the wechat service.
     */
    public function __construct(Application $wechat)
    {
        $this->wechat = $wechat;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param string|null              $account
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $account = null)
    {
        $isNewSession = false;
        $onlyRedirectInWeChatBrowser = config('entwechat.oauth.only_wechat_browser', false);
        $scopes = config('entwechat.oauth.scopes', ['snsapi_base']);

        if (!is_null($account)) {
            $this->wechat = $this->wechat->account($account);
            $onlyRedirectInWeChatBrowser = config("entwechat.account.{$account}.oauth.only_wechat_browser", false);
            $scopes = config("entwechat.account.{$account}.oauth.scopes", ['snsapi_base']);
        }

        if ($onlyRedirectInWeChatBrowser && !$this->isWeChatBrowser($request)) {
            if (config('debug')) {
                Log::debug('[not wechat browser] skip wechat oauth redirect.');
            }

            return $next($request);
        }

        if (is_string($scopes)) {
            $scopes = array_map('trim', explode(',', $scopes));
        }

        if (!session('entwechat.oauth_user') || $this->needReauth($scopes)) {
            if ($request->has('code')) {
                session(['entwechat.oauth_user' => $this->wechat->oauth->user()]);
                $isNewSession = true;

                Event::fire(new WeChatUserAuthorized(session('entwechat.oauth_user'), $isNewSession));

                return redirect()->to($this->getTargetUrl($request));
            }

            session()->forget('entwechat.oauth_user');

            return $this->wechat->oauth->scopes($scopes)->redirect($request->fullUrl());
        }

        Event::fire(new WeChatUserAuthorized(session('entwechat.oauth_user'), $isNewSession));

        return $next($request);
    }

    /**
     * Build the target business url.
     *
     * @param Request $request
     *
     * @return string
     */
    protected function getTargetUrl($request)
    {
        $queries = array_except($request->query(), ['code', 'state']);

        return $request->url().(empty($queries) ? '' : '?'.http_build_query($queries));
    }

    /**
     * Is different scopes.
     *
     * @param array $scopes
     *
     * @return bool
     */
    protected function needReauth($scopes)
    {
        return session('entwechat.oauth_user.original.scope') == 'snsapi_base' && in_array('snsapi_userinfo', $scopes);
    }

    /**
     * Detect current user agent type.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    protected function isWeChatBrowser($request)
    {
        return strpos($request->header('user_agent'), 'MicroMessenger') !== false;
    }
}
