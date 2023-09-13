using Android.OS;
using Microsoft.Maui.Controls;

namespace PfMaui;

public partial class MainPage : ContentPage
{
    IDispatcherTimer urlRefreshTimer = Application.Current.Dispatcher.CreateTimer();
    IDispatcherTimer settingsButtonTimer = Application.Current.Dispatcher.CreateTimer();
    public MainPage(MainPageViewModel vm)
    {
        InitializeComponent();
        BindingContext = vm;

        urlRefreshTimer.Interval = TimeSpan.FromMinutes(1);
        urlRefreshTimer.Tick += (s, e) => RefreshUrl();
        urlRefreshTimer.Start();

        settingsButtonTimer.Interval = TimeSpan.FromSeconds(5);
        settingsButtonTimer.Tick += (s, e) => HideButton();
        settingsButtonTimer.Start();

        //PowerManager pm = (PowerManager)MainActivity.MainActivityInstance.GetSystemService("PowerManager");

    }

    private void HideButton()
    {
        MainThread.BeginInvokeOnMainThread(() =>
        {
            btSettings.IsVisible = false;
        });
    }

    private void RefreshUrl()
    {
        MainThread.BeginInvokeOnMainThread(() =>
        {
            mainWebView.Reload();
            urlRefreshTimer.Start();
        });
    }

    private void Button_Clicked(object sender, EventArgs e)
    {
        MainActivity.MainActivityInstance.OpenAppSettings();
    }

    private void TapGestureRecognizer_Tapped(object sender, EventArgs e)
    {
        MainThread.BeginInvokeOnMainThread(() =>
        {
            btSettings.IsVisible = true;
            settingsButtonTimer.Start();
        });
    }
}

