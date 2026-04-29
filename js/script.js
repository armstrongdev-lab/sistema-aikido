// Função para calcular distância em km entre duas coordenadas (lat, lon)
function calcularDistancia(lat1, lon1, lat2, lon2) {
  function toRad(valor) {
    return valor * Math.PI / 180;
  }

  const R = 6371; // Raio da Terra em km
  const dLat = toRad(lat2 - lat1);
  const dLon = toRad(lon2 - lon1);

  const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
            Math.sin(dLon/2) * Math.sin(dLon/2);

  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

  return R * c; // distância em km
}

document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('btn-marcar-presenca');
  const statusEl = document.getElementById('status-presenca');

  if (!btn || !statusEl) return;

  const diasSemanaTexto = [
    'Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'
  ];

  function normalizarTexto(txt) {
    return txt.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
  }

  let horariosPermitidos = [];
  let dojoLatitude, dojoLongitude, distanciaPermitida;

  // Bloqueia o botão até os dados carregarem
  btn.disabled = true;

  // Busca horários permitidos e dados do dojo simultaneamente
  Promise.all([
    fetch('dias-permitidos.php').then(res => res.json()),
    fetch('coordenadas-dojo.php').then(res => res.json())
  ])
  .then(([diasData, dojoData]) => {
    if (diasData.error) {
      statusEl.style.color = 'red';
      statusEl.innerText = diasData.error;
      return;
    }
    if (dojoData.error) {
      statusEl.style.color = 'red';
      statusEl.innerText = dojoData.error;
      return;
    }

    horariosPermitidos = diasData.horariosPermitidos;
    dojoLatitude = parseFloat(dojoData.dojoLatitude);
    dojoLongitude = parseFloat(dojoData.dojoLongitude);
    distanciaPermitida = parseFloat(dojoData.distanciaPermitida);

    console.log('Horários permitidos:', horariosPermitidos);
    console.log('Coordenadas do dojo:', dojoLatitude, dojoLongitude, distanciaPermitida);

    const agora = new Date();
    console.log('Hora do navegador:', agora.toTimeString());
    console.log('Dia do navegador:', agora.toLocaleDateString('pt-BR', { weekday: 'long' }));

    btn.disabled = false; // libera o botão após tudo carregar
  })
  .catch(() => {
    statusEl.style.color = 'red';
    statusEl.innerText = 'Erro ao carregar dados iniciais.';
  });

  btn.addEventListener('click', () => {
    if (!navigator.geolocation) {
      alert('Geolocalização não suportada pelo seu navegador.');
      return;
    }

    if (dojoLatitude === undefined || dojoLongitude === undefined || distanciaPermitida === undefined) {
      alert('Não foi possível obter as coordenadas do dojo. Tente novamente mais tarde.');
      return;
    }

    const agora = new Date();
    const diaSemanaHoje = agora.getDay();
    const diaHojeTexto = diasSemanaTexto[diaSemanaHoje];
    const horaAgora = agora.getHours();
    const minutoAgora = agora.getMinutes();

    const permitidoAgora = horariosPermitidos.filter(item => {
  const diaBanco = normalizarTexto(item.dia_semana || '');
  const diaHojeNormalizado = normalizarTexto(diaHojeTexto);

  if (diaBanco === diaHojeNormalizado) {
    const [h, m] = item.horario.split(':').map(Number);
    const minutosAgora = horaAgora * 60 + minutoAgora;
    const minutosPermitidos = h * 60 + m;
    const diffMinutos = minutosAgora - minutosPermitidos;

    console.log('----');
    console.log('Horário permitido:', h, m);
    console.log('Minutos agora:', minutosAgora);
    console.log('Minutos permitidos:', minutosPermitidos);
    console.log('Diferença:', diffMinutos);
    console.log('Dia banco:', diaBanco, '| Dia atual:', diaHojeNormalizado);

    return diffMinutos >= -15 && diffMinutos <= 75;
  }
  return false;
});


    if (permitidoAgora.length === 0) {
      alert('Você não pode marcar presença fora do horário permitido.');
      statusEl.style.color = 'red';
      statusEl.innerText = 'Presença só permitida no horário de treino do dojo.';
      return;
    }

    navigator.geolocation.getCurrentPosition((position) => {
      const lat = position.coords.latitude;
      const lon = position.coords.longitude;

      const dist = calcularDistancia(lat, lon, dojoLatitude, dojoLongitude);

      statusEl.style.display = 'block';
      statusEl.style.whiteSpace = 'pre-line';
      statusEl.style.color = dist <= distanciaPermitida ? 'green' : 'red';

      if (dist <= distanciaPermitida) {
        fetch('marcar-presenca.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({})
        })
        .then(res => res.json())
        .then(data => {
          statusEl.innerText = `Distância até o dojo: ${(dist * 1000).toFixed(1)} m\n${data.message}`;
          if (data.success) {
            statusEl.className = 'sucesso';
            btn.disabled = true;
            setTimeout(() => window.location.reload(), 2000);
          } else {
            statusEl.className = 'erro';
          }
        })
        .catch(() => {
          statusEl.innerText = `Distância até o dojo: ${dist.toFixed(3)} m\nErro ao marcar presença.`;
          statusEl.className = 'erro';
        });
      } else {
        alert('Você não está no local autorizado para marcar presença.');
        statusEl.innerText = `Distância até o dojo: ${dist.toFixed(3)} m\nVocê não está no local autorizado para marcar presença.`;
        statusEl.className = 'erro';
      }
    }, () => {
      alert('Não foi possível obter sua localização.');
    });
  });
});
