using CommunityToolkit.Mvvm.ComponentModel;

namespace PfMaui
{
    public partial class MainPageViewModel : ObservableObject
    {
        [ObservableProperty]
        public UrlWebViewSource sourceUrl = new() { Url = "https://your-server" };
    }
}
