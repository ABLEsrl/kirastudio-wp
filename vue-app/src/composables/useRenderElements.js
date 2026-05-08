const sleep = (ms) =>
  new Promise((resolve) => {
    setTimeout(resolve, ms);
  });

export const useRenderElements = (getChatElement) => {
  const renderDataLinks = (el) => {
    if (! el) {
      return;
    }

    el.querySelectorAll('a[href^="data:"]').forEach((link) => {
      if (link.hasAttribute('download')) {
        return;
      }

      let fileName = link.textContent?.trim() || 'download';

      if (link.href.includes('text/csv') && ! fileName.endsWith('.csv')) {
        fileName += '.csv';
      }

      link.setAttribute('download', fileName);
    });
  };

  const renderCharts = async (el) => {
    if (! el) {
      return;
    }

    const chartElements = el.querySelectorAll('canvas.chart-element:not([rendered])');

    if (chartElements.length === 0) {
      return;
    }

    const { default: Chart } = await import('chart.js/auto');

    const waitForPaint = () =>
      new Promise((resolve) => {
        requestAnimationFrame(() => {
          requestAnimationFrame(resolve);
        });
      });
    const wait = (ms) =>
      new Promise((resolve) => {
        setTimeout(resolve, ms);
      });
    const minimumLoaderMs = 220;

    for (const element of chartElements) {
      const chartType = element.dataset.type;
      const canvasWrap = element.closest('.canvas-wrap');
      const startedAt = Date.now();

      try {
        const labelsRaw = element.dataset.labels ?? element.getAttribute('data-labels') ?? '';
        const datasetsRaw = element.dataset.datasets ?? element.getAttribute('data-datasets') ?? '';

        if (! labelsRaw || ! datasetsRaw) {
          return;
        }

        const labels = JSON.parse(labelsRaw.replace(/\n/g, ''));
        const datasets = JSON.parse(datasetsRaw.replace(/\n/g, ''));

        element.removeAttribute('data-type');
        element.removeAttribute('data-labels');
        element.removeAttribute('data-datasets');

        new Chart(element, {
          type: chartType,
          data: {
            labels,
            datasets,
          },
        });

        await waitForPaint();
        const elapsed = Date.now() - startedAt;

        if (elapsed < minimumLoaderMs) {
          await wait(minimumLoaderMs - elapsed);
        }

        element.setAttribute('rendered', 'true');
        canvasWrap?.setAttribute('data-render-ready', 'true');
      } catch (error) {
        // ignore malformed chart payloads
        canvasWrap?.setAttribute('data-render-ready', 'true');
      }
    }
  };

  const renderHtmlElements = async () => {
    await sleep(300);
    const elementSource = typeof getChatElement === 'function' ? getChatElement() : getChatElement;
    const chatElement = elementSource?.value ?? elementSource;

    if (! chatElement) {
      return;
    }

    renderDataLinks(chatElement);
    await renderCharts(chatElement);
  };

  return {
    renderHtmlElements,
    renderDataLinks,
    renderCharts,
  };
};
