<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use League\OAuth2\Client\Provider\GenericProvider;

class HemisOAuthController extends Controller
{
    private function getProvider(): GenericProvider
    {
        return new GenericProvider([
            'clientId'                => Setting::get('hemis.oauth.client_id', ''),
            'clientSecret'            => Setting::get('hemis.oauth.client_secret', ''),
            'redirectUri'             => Setting::get('hemis.oauth.redirect_uri', route('hemis.callback.employee')),
            'urlAuthorize'            => Setting::get('hemis.oauth.url_authorize', 'https://hemis.namdu.uz/oauth/authorize'),
            'urlAccessToken'          => Setting::get('hemis.oauth.url_access_token', 'https://hemis.namdu.uz/oauth/access-token'),
            'urlResourceOwnerDetails' => Setting::get('hemis.oauth.url_user_info', 'https://hemis.namdu.uz/oauth/api/user?fields=id,uuid,employee_id_number,type,roles,name,login,email,picture,firstname,surname,patronymic,birth_date,university_id,phone'),
        ]);
    }

    public function redirect(Request $request)
    {
        $provider = $this->getProvider();
        $authorizationUrl = $provider->getAuthorizationUrl();

        $request->session()->put('oauth2state', $provider->getState());

        return redirect()->away($authorizationUrl);
    }

    public function callback(Request $request)
    {   
        $state = $request->input('state');
        $savedState = $request->session()->pull('oauth2state');

        if (empty($state) || $state !== $savedState) {
            return redirect()->route('login')->withErrors(['email' => 'Yaroqsiz OAuth holati (sessiya eskirgan yoki xavfsizlik xatosi).']);
        }

        try {
            $provider = $this->getProvider();
            
            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $request->input('code')
            ]);

            $resourceOwner = $provider->getResourceOwner($accessToken);
            $userArray = $resourceOwner->toArray();

            // Extrapolate values gracefully from HEMIS payloads
            $email = $userArray['email'] ?? null;
            $login = $userArray['login'] ?? null;
            $hemisId = $userArray['employee_id_number'] ?? $userArray['id'] ?? $userArray['uuid'] ?? $login;
            $name = $userArray['name'] ?? trim(($userArray['firstname'] ?? '') . ' ' . ($userArray['surname'] ?? ''));
            
            if (!$email && !$hemisId) {
                return redirect()->route('login')->withErrors(['email' => 'HEMIS profilida yetarli ma\'lumot topilmadi (ID yoki email).']);
            }

            // Construct an identifier (fallback to hemis.local if email doesn't exist)
            $userEmail = $email ?: ($hemisId . '@hemis.local');

            $user = User::firstOrCreate(
                ['employee_id_number' => $hemisId],
                [
                    'email' => $userEmail,
                    'name' => $name ?: 'HEMIS User',
                    'password' => bcrypt(str()->random(24)),
                ]
            );

            // optionally update the email if they have a real one now, but firstOrCreate handles creation
            if ($email && str_ends_with($user->email, '@hemis.local')) {
                $user->update(['email' => $email]);
            }
            
            Auth::login($user);
            return redirect()->route('dashboard');

        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            return redirect()->route('login')->withErrors(['email' => 'HEMIS avtorizatsiyasida xatolik: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'Tizim xatosi: ' . $e->getMessage()]);
        }
    }
}
